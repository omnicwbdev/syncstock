<?php

require 'vendor/autoload.php'; // Carrega o Composer autoloader para phpdotenv

use Dotenv\Dotenv;

// Carregar variáveis do arquivo .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurações de conexão a partir do .env
$firebird_config = [
    'dbname'   => getenv('FIREBIRD_DBNAME'),
    'username' => getenv('FIREBIRD_USERNAME'),
    'password' => getenv('FIREBIRD_PASSWORD'),
    'charset'  => getenv('FIREBIRD_CHARSET')
];

$mysql_config = [
    'host'     => getenv('MYSQL_HOST'),
    'dbname'   => getenv('MYSQL_DBNAME'),
    'username' => getenv('MYSQL_USERNAME'),
    'password' => getenv('MYSQL_PASSWORD'),
    'charset'  => getenv('MYSQL_CHARSET')
];

// Função para logar mensagens
function logMessage($message, $logFile = 'sincronizacao.log')
{
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

// Função para conectar Firebird
function conectarFirebird($config)
{
    $dsn = "firebird:dbname={$config['dbname']};charset={$config['charset']}";
    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        logMessage("Erro ao conectar no Firebird: " . $e->getMessage());
        die("Erro ao conectar no Firebird: " . $e->getMessage());
    }
}

// Função para conectar MySQL
function conectarMySQL($config)
{
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        logMessage("Erro ao conectar no MySQL: " . $e->getMessage());
        die("Erro ao conectar no MySQL: " . $e->getMessage());
    }
}

// Função principal de sincronização
function sincronizarEstoque($pdo_firebird, $pdo_mysql)
{
    $start_time = microtime(true);

    $sql_firebird = "
        SELECT 
            e.ID_ESTOQUE, 
            e.DESCRICAO, 
            p.QTD_ATUAL, 
            e.PRC_CUSTO, 
            i.VALOR AS PRC_DOLAR
        FROM TB_ESTOQUE e
        JOIN TB_EST_PRODUTO p 
            ON e.ID_ESTOQUE = p.ID_IDENTIFICADOR
        LEFT JOIN TB_EST_INDEXADOR i 
            ON i.ID_ESTOQUE = e.ID_ESTOQUE
        WHERE e.STATUS = 'A'
    ";

    try {
        // Buscar dados do Firebird
        $stmt = $pdo_firebird->query($sql_firebird);
        $dados = $stmt->fetchAll();
        $total_lidos = count($dados);

        if ($total_lidos === 0) {
            logMessage("Nenhum dado encontrado no Firebird.");
            return [
                'lidos' => 0,
                'inseridos' => 0,
                'atualizados' => 0,
                'tempo' => 0
            ];
        }

        logMessage("Total de registros encontrados: $total_lidos");

        // Preparar INSERT/UPDATE para MySQL com múltiplos valores
        $batch_size = 1000; // Processar em lotes de 1000 registros
        $inseridos = 0;
        $atualizados = 0;

        $pdo_mysql->beginTransaction();

        for ($i = 0; $i < $total_lidos; $i += $batch_size) {
            $batch = array_slice($dados, $i, $batch_size);
            $values = [];
            $params = [];

            foreach ($batch as $index => $linha) {
                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = array_merge($params, [
                    $linha['ID_ESTOQUE'],
                    $linha['DESCRICAO'] ?? '',
                    $linha['QTD_ATUAL'] ?? 0,
                    $linha['PRC_CUSTO'] ?? 0,
                    $linha['PRC_DOLAR'] ?? 0,
                    0.00, // PRC_VENDA
                    0.00, // PRC_3X
                    0.00, // PRC_6X
                    0.00  // PRC_10X
                ]);
            }

            $sql_insert = "INSERT INTO TB_ESTOQUE (ID_ESTOQUE, DESCRICAO, QTD_ATUAL, PRC_CUSTO, PRC_DOLAR, PRC_VENDA, PRC_3X, PRC_6X, PRC_10X) 
                           VALUES " . implode(',', $values) . "
                           ON DUPLICATE KEY UPDATE 
                           DESCRICAO = VALUES(DESCRICAO),
                           QTD_ATUAL = VALUES(QTD_ATUAL),
                           PRC_CUSTO = VALUES(PRC_CUSTO),
                           PRC_DOLAR = VALUES(PRC_DOLAR),
                           PRC_VENDA = VALUES(PRC_VENDA),
                           PRC_3X = VALUES(PRC_3X),
                           PRC_6X = VALUES(PRC_6X),
                           PRC_10X = VALUES(PRC_10X)";

            $stmt_mysql = $pdo_mysql->prepare($sql_insert);
            $stmt_mysql->execute($params);

            $rowCount = $stmt_mysql->rowCount();
            $inseridos += ($rowCount == count($batch)) ? count($batch) : 0;
            $atualizados += ($rowCount == 2 * count($batch)) ? count($batch) : ($rowCount - $inseridos);
        }

        $pdo_mysql->commit();

        // Chamar stored procedures após o commit
        logMessage("Executando stored procedure UpdateQtdVirtual...");
        $pdo_mysql->exec("CALL UpdateQtdVirtual()");
        logMessage("Stored procedure UpdateQtdVirtual executada com sucesso.");

        logMessage("Executando stored procedure SP_ATUALIZAR_PART_NUMBER...");
        $pdo_mysql->exec("CALL SP_ATUALIZAR_PART_NUMBER()");
        logMessage("Stored procedure SP_ATUALIZAR_PART_NUMBER executada com sucesso.");

        $end_time = microtime(true);
        $tempo_execucao = round($end_time - $start_time, 2);

        $estatisticas = [
            'lidos' => $total_lidos,
            'inseridos' => $inseridos,
            'atualizados' => $atualizados,
            'tempo' => $tempo_execucao
        ];

        logMessage("Sincronização concluída!");
        logMessage("Registros lidos: $total_lidos");
        logMessage("Registros inseridos: $inseridos");
        logMessage("Registros atualizados: $atualizados");
        logMessage("Tempo de execução: $tempo_execucao segundos");

        return $estatisticas;

    } catch (Exception $e) {
        $pdo_mysql->rollBack();
        logMessage("Erro na sincronização ou execução de stored procedures: " . $e->getMessage());
        die("Erro na sincronização ou execução de stored procedures: " . $e->getMessage());
    }
}

// EXECUÇÃO PRINCIPAL
logMessage("=== INICIANDO SINCRONIZAÇÃO FIREBIRD -> MySQL ===");
$start_total = microtime(true);

try {
    // Conectar aos bancos
    logMessage("Conectando aos bancos de dados...");
    $pdo_firebird = conectarFirebird($firebird_config);
    $pdo_mysql = conectarMySQL($mysql_config);

    logMessage("Conexões estabelecidas com sucesso!");

    // Executar sincronização
    $estatisticas = sincronizarEstoque($pdo_firebird, $pdo_mysql);

} catch (Exception $e) {
    logMessage("Erro geral: " . $e->getMessage());
    echo "Erro geral: " . $e->getMessage() . "\n";
}

$end_total = microtime(true);
$tempo_total = round($end_total - $start_total, 2);

// Exibir estatísticas finais
logMessage("=== SINCRONIZAÇÃO FINALIZADA ===");
logMessage("Tempo total de execução: $tempo_total segundos");
logMessage("Estatísticas: " . json_encode($estatisticas));

// Fechar conexões
$pdo_firebird = null;
$pdo_mysql = null;
