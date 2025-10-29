# 1. Build com PHP 8.2
docker-compose down -v
docker-compose build --no-cache

# 2. Sobe
docker-compose up -d

# 3. Verifica extensão pdo_firebird
docker exec -it sync php -m | grep firebird
# Saída esperada: pdo_firebird

# 4. Verifica versão PHP
docker exec -it sync php -v
# Saída esperada: PHP 8.2.x (cli)

# 5. Teste rápido de conexão Firebird (ajuste host/db)
docker exec -it sync php -r "
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
docker exec -it sync php sync/sync.php

# 7. Ver log
docker exec -it sync tail -f sincronizacao.log

# 8. Debug composer
docker exec -it sync composer install -vvv

# Extras
docker exec -it sync composer dump-autoload
docker exec -it sync php src/sync.php

# Verificar se as dependências foram instaladas
docker exec -it sync ls -la /app/vendor/

# Deve mostrar vlucas/phpdotenv e outras pastas
docker exec -it sync ls -la /app/vendor/vlucas/

# Crontab

```
*/15 * * * * docker run --rm --env-file /opt/sync/.env -v /opt/sync:/app --name sync-job waldirborbajr/sync-prod >> /var/log/sync.log 2>&1

```

```
```
