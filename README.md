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


# Comandos de Qualidade de Código:

```sh
    make lint - Analisa código com PHP Code Sniffer (PSR12)

    make lint-fix - Corrige automaticamente problemas de estilo

    make analyse - Análise estática com PHPStan (nível 8)
```

# Comandos de Desenvolvimento:

```sh
    make debug - Executa script com Xdebug habilitado

    make test - Executa testes unitários

    make shell - Alias para make exec
```

# Comandos de Infraestrutura:

```sh
    make nginx - Inicia servidor web para desenvolvimento

    make nginx-stop - Para o servidor web

    make composer-install - Instala dependências

    make composer-update - Atualiza dependências

    make dev-deps - Instala dependências de desenvolvimento
```

Uso Prático:
bash

# Fluxo típico de desenvolvimento
make build
make up
make lint          # Verificar qualidade do código
make analyse       # Análise estática
make debug         # Executar com Xdebug

# Ou para desenvolvimento web
make nginx         # Servidor web em localhost:8080

# Instalar ferramentas de desenvolvimento
make dev-deps      # Instala PHPStan, PHPUnit, etc.


# Recriar o container com as novas configurações

make stop
make build
make up

# Verificar se as configurações foram carregadas
make exec

# Dentro do container:
php -i | grep display_errors
php -i | grep xdebug.mode

# Testar Xdebug
make debug


# Desenvolvimento (padrão)
make dockerignore-dev
make build-dev
make up

# Produção
make dockerignore-prod  
make build-prod

# Ou use os atalhos
make build-dev    # Desenvolvimento completo
make build-prod   # Produção completa

# Verificar configuração atual
make dockerignore-show



--------

# Configurar para desenvolvimento
make dockerignore-dev
make dockerignore-show

# Configurar para produção  
make dockerignore-prod
make dockerignore-show

# Build de desenvolvimento
make build-dev

# Build de produção
make build-prod

# Ou use os atalhos completos
make dev      # Desenvolvimento completo
make prod     # Produção completa
