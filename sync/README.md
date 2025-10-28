# 1. Build com PHP 8.2
docker-compose down -v
docker-compose build --no-cache

# 2. Sobe
docker-compose up -d

# 3. Verifica extensão pdo_firebird
docker exec -it sync-dev php -m | grep firebird
# Saída esperada: pdo_firebird

# 4. Verifica versão PHP
docker exec -it sync-dev php -v
# Saída esperada: PHP 8.2.x (cli)

# 5. Teste rápido de conexão Firebird (ajuste host/db)
docker exec -it sync-dev php -r "
try {
    \$pdo = new PDO('firebird:dbname=127.0.0.1:/path/EMPRESA.FDB;charset=UTF8', 'SYSDBA', 'masterkey');
    echo 'PDO_FIREBIRD OK no PHP 8.2!' . PHP_EOL;
    \$stmt = \$pdo->query('SELECT FIRST 1 ID_ESTOQUE FROM TB_ESTOQUE');
    print_r(\$stmt->fetch());
} catch (Exception \$e) {
    echo 'Erro: ' . \$e->getMessage() . PHP_EOL;
}
"

# 6. Executa o sync
docker exec -it sync-dev php sync/sync.php

# 7. Ver log
docker exec -it sync-dev tail -f sincronizacao.log

