[![.github/workflows/docker-ci.yaml](https://github.com/omnicwbdev/syncstock/actions/workflows/docker-ci.yaml/badge.svg)](https://github.com/omnicwbdev/syncstock/actions/workflows/docker-ci.yaml) [![CodeQL](https://github.com/omnicwbdev/syncstock/actions/workflows/github-code-scanning/codeql/badge.svg)](https://github.com/omnicwbdev/syncstock/actions/workflows/github-code-scanning/codeql) [![Dependabot Updates](https://github.com/omnicwbdev/syncstock/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/omnicwbdev/syncstock/actions/workflows/dependabot/dependabot-updates) [![PHP Composer](https://github.com/omnicwbdev/syncstock/actions/workflows/php.yaml/badge.svg)](https://github.com/omnicwbdev/syncstock/actions/workflows/php.yaml)

# SyncStock

Ferramenta para sincronizar estoque do Firebird para MySQL, calcular preços e executar rotinas pós-sincronização.

Resumo
-------
Sincronização programável com configuração via .env, suporte a execução em container Docker e comandos úteis via Makefile.

Ambiente de desenvolvimento
---------------------------
- Container dev: Ubuntu 24.04.2 LTS
- Ferramentas disponíveis: docker, docker-compose, make, php, composer
- Para abrir URLs no host a partir do container use: "$BROWSER" <url>

Requisitos
----------
- Docker & Docker Compose
- Make
- Arquivo `.env` configurado (use `.env.example`)

Arquivos principais
-------------------
- src/sync.php — lógica principal (conexões Firebird/MySQL, cálculo de preços)
- docker-compose.yaml — orquestração de containers
- Dockerfile.php8.4 / Dockerfile.prod — imagens dev/prod
- .env.example — variáveis de ambiente
- Makefile — atalhos de execução
- composer.json — dependências
- SECURITY.md — política de segurança

Quickstart (rápido)
-------------------
1. Copiar variáveis de ambiente:
   cp .env.example .env
   editar .env

2. Build e subir containers:
   make build
   make up

3. Rodar sincronização:
   make run
   ou
   docker exec -it sync php src/sync.php

Comandos úteis (Makefile)
-------------------------
- make build    — build das imagens
- make up       — sobe containers em background
- make run      — executa o script de sincronização
- make exec     — shell interativo no container
- make logs     — segue logs do serviço
- make lint     — verifica estilo (PSR12)
- make analyse  — PHPStan
- make test     — PHPUnit

Configuração
------------
- Preencher `.env` com credenciais Firebird e MySQL e parâmetros de preço (LUCRO, PARC3X, etc).
- Verificar extensões PHP necessárias (ex.: PDO Firebird).

Verificação e depuração
-----------------------
- Verificar extensão Firebird:
  docker exec -it sync php -m | grep firebird

- Ver logs:
  docker-compose logs -f

- Entrar no container:
  make exec

Dicas de produção
-----------------
- Use Dockerfile.prod para imagens otimizadas.
- Execute com usuário não-root e proteja o `.env`.
- Rotinas críticas devem ser testadas em ambiente staging.

Contribuição
-----------
- Abra PRs e mantenha linter/PHPStan limpos.
- Reporte vulnerabilidades em SECURITY.md.

Licença
-------
Ver `composer.json` para informações da licença.

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
```
