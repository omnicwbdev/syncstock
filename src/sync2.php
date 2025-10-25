<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;

// Carregar variáveis do arquivo .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurações de conexão
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

// Configurações de negócio
$business_config = [
    'lucro'   => (float) getenv('LUCRO_PERCENTUAL'),
    'parc3x'  => (float) getenv('PARCELA_3X_PERCENTUAL'),
    'parc6x'  => (float) getenv('PARCELA_6X_PERCENTUAL'),
    'parc10x' => (float) getenv('PARCELA_10X_PERCENTUAL'),
    'debug_mode' => getenv('DEBUG_MODE') === 'true'
];

// Estatísticas de processamento
$processing_stats = [
    'load_time' => 0,
    'query_time' => 0,
    'processing_time' => 0,
    'procedure_time' => 0,
    'total_rows' => 0,
    'inserted' => 0,
    'updated' => 0,
    'ignored' => 0
];

// Função para logar mensagens
function logMessage($message, $logFile = 'sincronizacao.log') {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
    if ($GLOBALS['business_config']['debug_mode']) {
        echo date('Y-m-d H:i:s') . " - $message\n";
    }
}

// Função para debug detalhado
function logDebug($context, $data) {
    if ($GLOBALS['business_config']['debug_mode']) {
        $message = $context . ": " . json_encode($data, JSON_PRETTY_PRINT);
        logMessage("[DEBUG] $message");
    }
}

// Função para conectar Firebird
function conectarFirebird($config) {
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
function conectarMySQL($config) {
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

// Função para carregar registros existentes do MySQL
function carregarRegistrosMySQL($pdo_mysql) {
    $start_time = microtime(true);
    
    $records = [];
    
    // Primeiro contar os registros
    $stmt = $pdo_mysql->query("SELECT COUNT(*) as total FROM TB_ESTOQUE WHERE ID_ESTOQUE IS NOT NULL");
    $count = $stmt->fetchColumn();
    
    logMessage("Total de registros no MySQL: $count");
    
    // Carregar todos os registros
    $stmt = $pdo_mysql->query("
        SELECT ID_ESTOQUE, DESCRICAO, QTD_ATUAL, PRC_CUSTO, PRC_DOLAR, PRC_VENDA, PRC_3X, PRC_6X, PRC_10X 
        FROM TB_ESTOQUE 
        WHERE ID_ESTOQUE IS NOT NULL
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $records[$row['ID_ESTOQUE']] = [
            'DESCRICAO' => $row['DESCRICAO'],
            'QTD_ATUAL' => (float) $row['QTD_ATUAL'],
            'PRC_CUSTO' => (float) $row['PRC_CUSTO'],
            'PRC_DOLAR' => (float) $row['PRC_DOLAR'],
            'PRC_VENDA' => (float) $row['PRC_VENDA'],
            'PRC_3X' => (float) $row['PRC_3X'],
            'PRC_6X' => (float) $row['PRC_6X'],
            'PRC_10X' => (float) $row['PRC_10X']
        ];
    }
    
    $GLOBALS['processing_stats']['load_time'] = microtime(true) - $start_time;
    logMessage("Registros MySQL carregados: " . count($records));
    
    return $records;
}

// Função para calcular preços baseado nas regras de negócio
function calcularPrecos($prcCusto, $config) {
    if ($prcCusto == 0 || $prcCusto === null) {
        return [0, 0, 0, 0];
    }
    
    $lucro = $config['lucro'] / 100;
    $parc3x = $config['parc3x'] / 100;
    $parc6x = $config['parc6x'] / 100;
    $parc10x = $config['parc10x'] / 100;
    
    // PRC_VENDA = PRC_CUSTO * (1 + LUCRO/100)
    $prcVenda = $prcCusto * (1 + $lucro);
    $prcVenda = round($prcVenda, 2);
    
    // PRC_3X = (PRC_CUSTO * (1 + LUCRO/100) * (1 + PARC3X/100)) / 3
    $prc3x = ($prcCusto * (1 + $lucro) * (1 + $parc3x)) / 3;
    $prc3x = round($prc3x, 2);
    
    // PRC_6X = (PRC_CUSTO * (1 + LUCRO/100) * (1 + PARC6X/100)) / 6
    $prc6x = ($prcCusto * (1 + $lucro) * (1 + $parc6x)) / 6;
    $prc6x = round($prc6x, 2);
    
    // PRC_10X = (PRC_CUSTO * (1 + LUCRO/100) * (1 + PARC10X/100)) / 10
    $prc10x = ($prcCusto * (1 + $lucro) * (1 + $parc10x)) / 10;
    $prc10x = round($prc10x, 2);
    
    return [$prcVenda, $prc3x, $prc6x, $prc10x];
}

// Função para processar linha e decidir ação
function processarLinha($existingRecords, $idEstoque, $descricao, $qtdAtual, $prcCusto, $prcDolar, $config) {
    $prcCusto = $prcCusto ?? 0;
    $prcDolar = $prcDolar ?? 0;
    
    // Calcular os novos preços
    list($prcVenda, $prc3x, $prc6x, $prc10x) = calcularPrecos($prcCusto, $config);
    
    // Arredondar valores para comparação
    $prcCusto = round($prcCusto, 2);
    $prcDolar = round($prcDolar, 2);
    
    // Log da linha do Firebird (source)
    logDebug("Firebird Row", [
        'id_estoque' => $idEstoque,
        'descricao' => $descricao,
        'qtd_atual' => $qtdAtual,
        'prc_custo' => $prcCusto,
        'prc_dolar' => $prcDolar
    ]);
    
    $exists = isset($existingRecords[$idEstoque]);
    
    if ($exists) {
        $rec = $existingRecords[$idEstoque];
        
        // Log da linha do MySQL (target)
        logDebug("MySQL Row", [
            'id_estoque' => $idEstoque,
            'descricao' => $rec['DESCRICAO'],
            'qtd_atual' => $rec['QTD_ATUAL'],
            'prc_custo' => $rec['PRC_CUSTO'],
            'prc_dolar' => $rec['PRC_DOLAR'],
            'prc_venda' => $rec['PRC_VENDA'],
            'prc_3x' => $rec['PRC_3X'],
            'prc_6x' => $rec['PRC_6X'],
            'prc_10x' => $rec['PRC_10X']
        ]);
        
        // Comparar valores para decidir se atualiza
        $existingPrcCusto = round($rec['PRC_CUSTO'], 2);
        $existingPrcDolar = round($rec['PRC_DOLAR'], 2);
        $existingPrcVenda = round($rec['PRC_VENDA'], 2);
        $existingPrc3x = round($rec['PRC_3X'], 2);
        $existingPrc6x = round($rec['PRC_6X'], 2);
        $existingPrc10x = round($rec['PRC_10X'], 2);
        
        if ($rec['DESCRICAO'] === $descricao &&
            $rec['QTD_ATUAL'] == $qtdAtual &&
            $existingPrcCusto == $prcCusto &&
            $existingPrcDolar == $prcDolar &&
            $existingPrcVenda == $prcVenda &&
            $existingPrc3x == $prc3x &&
            $existingPrc6x == $prc6x &&
            $existingPrc10x == $prc10x) {
            
            $GLOBALS['processing_stats']['ignored']++;
            logDebug("Registro ignorado", ['id_estoque' => $idEstoque]);
            return ['action' => 'ignore', 'params' => null];
        }
        
        // Atualizar registro
        $GLOBALS['processing_stats']['updated']++;
        logMessage("Atualizando registro ID: $idEstoque");
        
        return [
            'action' => 'update', 
            'params' => [
                $descricao, $qtdAtual, $prcCusto, $prcDolar, 
                $prcVenda, $prc3x, $prc6x, $prc10x, $idEstoque
            ]
        ];
    } else {
        // Inserir novo registro
        $GLOBALS['processing_stats']['inserted']++;
        logMessage("Inserindo novo registro ID: $idEstoque");
        
        return [
            'action' => 'insert', 
            'params' => [
                $idEstoque, $descricao, $qtdAtual, $prcCusto, $prcDolar,
                $prcVenda, $prc3x, $prc6x, $prc10x
            ]
        ];
    }
}

// Função principal de sincronização
function sincronizarEstoque($pdo_firebird, $pdo_mysql, $existingRecords) {
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
        $query_start = microtime(true);
        $stmt = $pdo_firebird->query($sql_firebird);
        $dados = $stmt->fetchAll();
        $GLOBALS['processing_stats']['query_time'] = microtime(true) - $query_start;
        
        $total_lidos = count($dados);
        $GLOBALS['processing_stats']['total_rows'] = $total_lidos;
        
        if ($total_lidos === 0) {
            logMessage("Nenhum dado encontrado no Firebird.");
            return;
        }
        
        logMessage("Total de registros encontrados no Firebird: $total_lidos");
        
        // Preparar batches
        $batch_size = 1000;
        $insert_batch = [];
        $update_batch = [];
        
        $pdo_mysql->beginTransaction();
        
        $processing_start = microtime(true);
        
        foreach ($dados as $linha) {
            $idEstoque = $linha['ID_ESTOQUE'];
            $descricao = $linha['DESCRICAO'] ?? '';
            $qtdAtual = (float) ($linha['QTD_ATUAL'] ?? 0);
            $prcCusto = isset($linha['PRC_CUSTO']) ? (float) $linha['PRC_CUSTO'] : 0;
            $prcDolar = isset($linha['PRC_DOLAR']) ? (float) $linha['PRC_DOLAR'] : 0;
            
            $result = processarLinha($existingRecords, $idEstoque, $descricao, $qtdAtual, $prcCusto, $prcDolar, $GLOBALS['business_config']);
            
            if ($result['action'] === 'insert') {
                $insert_batch[] = $result['params'];
            } elseif ($result['action'] === 'update') {
                $update_batch[] = $result['params'];
            }
            
            // Processar batches quando atingir o tamanho
            if (count($insert_batch) >= $batch_size) {
                processarBatchInsert($pdo_mysql, $insert_batch);
                $insert_batch = [];
            }
            
            if (count($update_batch) >= $batch_size) {
                processarBatchUpdate($pdo_mysql, $update_batch);
                $update_batch = [];
            }
        }
        
        // Processar batches finais
        if (!empty($insert_batch)) {
            processarBatchInsert($pdo_mysql, $insert_batch);
        }
        
        if (!empty($update_batch)) {
            processarBatchUpdate($pdo_mysql, $update_batch);
        }
        
        $GLOBALS['processing_stats']['processing_time'] = microtime(true) - $processing_start;
        
        $pdo_mysql->commit();
        
        // Verificar registro específico (como no GO)
        if ($GLOBALS['processing_stats']['updated'] > 0) {
            verificarRegistroAtualizado($pdo_mysql, 17973);
        }
        
        // Executar stored procedures
        $procedure_start = microtime(true);
        executarStoredProcedures($pdo_mysql);
        $GLOBALS['processing_stats']['procedure_time'] = microtime(true) - $procedure_start;
        
        $GLOBALS['processing_stats']['processing_time'] = microtime(true) - $start_time;
        
    } catch (Exception $e) {
        $pdo_mysql->rollBack();
        logMessage("Erro na sincronização: " . $e->getMessage());
        throw $e;
    }
}

// Função para processar batch de insert
function processarBatchInsert($pdo_mysql, $batch) {
    if (empty($batch)) return;
    
    $placeholders = [];
    $params = [];
    
    foreach ($batch as $row) {
        $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $params = array_merge($params, $row);
    }
    
    $sql = "INSERT INTO TB_ESTOQUE (ID_ESTOQUE, DESCRICAO, QTD_ATUAL, PRC_CUSTO, PRC_DOLAR, PRC_VENDA, PRC_3X, PRC_6X, PRC_10X) 
            VALUES " . implode(',', $placeholders);
    
    $stmt = $pdo_mysql->prepare($sql);
    $stmt->execute($params);
    
    logDebug("Batch Insert Executado", ['registros' => count($batch)]);
}

// Função para processar batch de update
function processarBatchUpdate($pdo_mysql, $batch) {
    if (empty($batch)) return;
    
    // Para update, precisamos executar um por um devido à cláusula WHERE
    $sql = "UPDATE TB_ESTOQUE SET 
            DESCRICAO = ?, QTD_ATUAL = ?, PRC_CUSTO = ?, PRC_DOLAR = ?, 
            PRC_VENDA = ?, PRC_3X = ?, PRC_6X = ?, PRC_10X = ? 
            WHERE ID_ESTOQUE = ?";
    
    $stmt = $pdo_mysql->prepare($sql);
    
    foreach ($batch as $row) {
        $stmt->execute($row);
        logDebug("Update Executado", ['id_estoque' => $row[8]]);
    }
}

// Função para verificar registro atualizado (como no GO)
function verificarRegistroAtualizado($pdo_mysql, $idEstoque) {
    $stmt = $pdo_mysql->prepare("
        SELECT DESCRICAO, QTD_ATUAL, PRC_CUSTO, PRC_DOLAR, PRC_VENDA, PRC_3X, PRC_6X, PRC_10X 
        FROM TB_ESTOQUE 
        WHERE ID_ESTOQUE = ?
    ");
    
    $stmt->execute([$idEstoque]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        logMessage("Registro verificado após update - ID: $idEstoque");
        logDebug("Registro Verificado", [
            'id_estoque' => $idEstoque,
            'descricao' => $row['DESCRICAO'],
            'qtd_atual' => $row['QTD_ATUAL'],
            'prc_custo' => $row['PRC_CUSTO'],
            'prc_dolar' => $row['PRC_DOLAR'],
            'prc_venda' => $row['PRC_VENDA'],
            'prc_3x' => $row['PRC_3X'],
            'prc_6x' => $row['PRC_6X'],
            'prc_10x' => $row['PRC_10X']
        ]);
    }
}

// Função para executar stored procedures
function executarStoredProcedures($pdo_mysql) {
    logMessage("Executando stored procedure UpdateQtdVirtual...");
    $pdo_mysql->exec("CALL UpdateQtdVirtual()");
    logMessage("Stored procedure UpdateQtdVirtual executada com sucesso.");
    
    logMessage("Executando stored procedure SP_ATUALIZAR_PART_NUMBER...");
    $pdo_mysql->exec("CALL SP_ATUALIZAR_PART_NUMBER()");
    logMessage("Stored procedure SP_ATUALIZAR_PART_NUMBER executada com sucesso.");
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
    
    // Carregar registros existentes do MySQL
    logMessage("Carregando registros existentes do MySQL...");
    $existingRecords = carregarRegistrosMySQL($pdo_mysql);
    
    // Executar sincronização
    sincronizarEstoque($pdo_firebird, $pdo_mysql, $existingRecords);
    
} catch (Exception $e) {
    logMessage("Erro geral: " . $e->getMessage());
    echo "Erro geral: " . $e->getMessage() . "\n";
}

$end_total = microtime(true);
$tempo_total = round($end_total - $start_total, 2);

// Exibir estatísticas finais
logMessage("=== SINCRONIZAÇÃO FINALIZADA ===");
logMessage("Tempo total de execução: $tempo_total segundos");
logMessage("Estatísticas: " . json_encode([
    'registros_lidos' => $processing_stats['total_rows'],
    'registros_inseridos' => $processing_stats['inserted'],
    'registros_atualizados' => $processing_stats['updated'],
    'registros_ignorados' => $processing_stats['ignored'],
    'tempo_carregamento' => round($processing_stats['load_time'], 2) . 's',
    'tempo_consulta' => round($processing_stats['query_time'], 2) . 's',
    'tempo_processamento' => round($processing_stats['processing_time'], 2) . 's',
    'tempo_procedures' => round($processing_stats['procedure_time'], 2) . 's'
], JSON_PRETTY_PRINT));

// Fechar conexões
$pdo_firebird = null;
$pdo_mysql = null;
