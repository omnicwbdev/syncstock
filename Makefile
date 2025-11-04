# Makefile para SyncStock (PHP 8.4 + Firebird + MySQL)
# Comandos para build, execução, debug e controle do container

.PHONY: build build-fast build-nc up start run exec logs stop restart clean help dangling \
        lint lint-fix analyse test debug shell nginx nginx-stop \
        composer-install composer-update dev-deps \
        dockerignore-dev dockerignore-prod dockerignore-show \
        build-dev build-prod build-nc-dev build-nc-prod doom

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================
CONTAINER =$(strip sync)
SCRIPT    =$(strip src/sync.php)
LOGFILE   =$(strip sincronizacao.log)
IMAGE_NAME =$(strip syncstock/sync)
SRC_DIR   =$(strip src/)
DOCKERIGNORE_DEV = .dockerignore.dev
DOCKERIGNORE_PROD = .dockerignore.prod

# =============================================================================
# COMANDOS PRINCIPAIS
# =============================================================================

help: ## Mostra esta ajuda (padrão)
	@echo "Makefile para SyncStock"
	@echo ""
	@echo "COMANDOS PRINCIPAIS:"
	@echo "  make build           → Build da imagem (latest)"
	@echo "  make build-fast      → Build rápido usando cache"
	@echo "  make build-nc        → Rebuild completo (com --no-cache)"
	@echo "  make up              → Sobe o container em background (-d)"
	@echo "  make start           → Sobe o container em foreground (sem -d)"
	@echo "  make run             → Executa o script de sincronização"
	@echo "  make exec            → Entra no container (bash)"
	@echo "  make logs            → Segue o log em tempo real"
	@echo "  make stop            → Para o container removendo orphans"
	@echo "  make restart         → Para + Build + Sobe + Executa"
	@echo "  make clean           → Remove container, volumes e limpa cache"
	@echo "  make dangling        → Remove imagens dangling (sem tag)"
	@echo ""
	@echo "COMANDOS DE DESENVOLVIMENTO:"
	@echo "  make lint            → Analisa código com PHP Code Sniffer"
	@echo "  make lint-fix        → Corrige automaticamente problemas de estilo"
	@echo "  make analyse         → Análise estática com PHPStan"
	@echo "  make test            → Executa testes unitários"
	@echo "  make debug           → Executa script com Xdebug habilitado"
	@echo "  make shell           → Entra no container (bash interativo)"
	@echo "  make nginx           → Inicia servidor web Nginx"
	@echo "  make nginx-stop      → Para servidor web Nginx"
	@echo "  make composer-install → Instala dependências do Composer"
	@echo "  make composer-update → Atualiza dependências do Composer"
	@echo "  make dev-deps        → Instala dependências de desenvolvimento"
	@echo ""
	@echo "DOCKERIGNORE:"
	@echo "  make dockerignore-dev  → Configura .dockerignore para desenvolvimento"
	@echo "  make dockerignore-prod → Configura .dockerignore para produção"
	@echo "  make dockerignore-show → Mostra configuração atual"
	@echo "  make build-dev         → Build para DESENVOLVIMENTO"
	@echo "  make build-prod        → Build para PRODUÇÃO"
	@echo "  make build-nc-dev      → Rebuild para desenvolvimento (no cache)"
	@echo "  make build-nc-prod     → Rebuild para produção (no cache)"
	@echo ""
	@echo "UTILITÁRIOS:"
	@echo "  make help             → Mostra esta ajuda"
	@echo "  make doom             → Apocalipse Now (limpeza total do Docker)"
	@echo ""

build: ## Build da imagem (latest) (recomendado)
	@echo "Building $(IMAGE_NAME):latest..."
	@docker compose build

build-fast: ## Build rápido usando cache (sem versão)
	@echo "Building $(IMAGE_NAME):latest (using cache)..."
	@docker compose build

build-nc: ## Rebuild completo com --no-cache
	@echo "Building $(IMAGE_NAME):latest (NO CACHE)..."
	@docker compose build --no-cache
	@echo "Build completo!"

up: ## Sobe o container em background (-d)
	@echo "Starting container in background..."
	@docker compose up -d

start: ## Sobe o container em foreground (sem -d)
	@echo "Starting container in foreground..."
	@docker compose up

run: ## Executa o script de sincronização
	@echo "Executing $(SCRIPT)..."
	@docker exec -it $(CONTAINER) php $(SCRIPT)

exec: ## Entra no container (bash interativo)
	@echo "Entering container $(CONTAINER)..."
	@docker exec -it $(CONTAINER) bash

logs: ## Segue o log da sincronização
	@echo "Following $(LOGFILE)..."
	@docker exec -it $(CONTAINER) tail -f $(LOGFILE)

stop: ## Para o container removendo containers órfãos
	@echo "Stopping containers and removing orphans..."
	@docker compose down --remove-orphans

restart: stop build up run ## Para + Build + Sobe + Executa
	@echo "Restart sequence completed!"

clean: ## Remove container, volumes e limpa imagens
	@echo "Cleaning up: removing containers, volumes, and pruning system..."
	@docker compose down -v --remove-orphans
	@docker system prune -f

dangling: ## Remove imagens dangling (sem tag) - <none>:<none>
	@echo "Removendo imagens dangling..."
	@DANGLING_IMAGES=$$(docker images -f "dangling=true" -q); \
	if [ -n "$$DANGLING_IMAGES" ]; then \
		echo "Removendo imagens: $$DANGLING_IMAGES"; \
		docker rmi $$DANGLING_IMAGES 2>/dev/null || echo "Algumas imagens não puderam ser removidas (em uso)"; \
	else \
		echo "Nenhuma imagem dangling encontrada."; \
	fi

# =============================================================================
# COMANDOS DE DESENVOLVIMENTO
# =============================================================================

lint: ## Analisa código com PHP Code Sniffer (PSR12)
	@echo "Analisando código com PHP Code Sniffer..."
	@docker exec -it $(CONTAINER) phpcs --standard=PSR12 $(SRC_DIR)

lint-fix: ## Corrige automaticamente problemas de estilo de código
	@echo "Corrigindo estilo de código..."
	@docker exec -it $(CONTAINER) phpcbf --standard=PSR12 $(SRC_DIR)

analyse: ## Análise estática com PHPStan (nível 8 - mais rigoroso)
	@echo "Executando análise estática com PHPStan..."
	@docker exec -it $(CONTAINER) phpstan analyse $(SRC_DIR) --level=8

test: ## Executa testes unitários
	@echo "Executando testes unitários..."
	@docker exec -it $(CONTAINER) ./vendor/bin/phpunit

debug: ## Executa script com Xdebug habilitado
	@echo "Executando $(SCRIPT) com Xdebug..."
	@docker exec -it $(CONTAINER) php -d xdebug.mode=debug $(SCRIPT)

shell: ## Entra no container (bash interativo) - alias para exec
	@echo "Abrindo shell no container..."
	@docker exec -it $(CONTAINER) bash

nginx: ## Inicia servidor web Nginx para desenvolvimento
	@echo "Iniciando servidor web Nginx..."
	@docker compose -f docker-compose.yml up -d nginx
	@echo "Nginx rodando em http://localhost:8080"

nginx-stop: ## Para servidor web Nginx
	@echo "Parando servidor web Nginx..."
	@docker compose -f docker-compose.yml stop nginx

composer-install: ## Instala dependências do Composer
	@echo "Instalando dependências do Composer..."
	@docker exec -it $(CONTAINER) composer install --no-interaction --optimize-autoloader

composer-update: ## Atualiza dependências do Composer
	@echo "Atualizando dependências do Composer..."
	@docker exec -it $(CONTAINER) composer update --no-interaction --optimize-autoloader

dev-deps: ## Instala dependências de desenvolvimento
	@echo "Instalando dependências de desenvolvimento..."
	@docker exec -it $(CONTAINER) composer require --dev \
		squizlabs/php_codesniffer \
		phpstan/phpstan \
		friendsofphp/php-cs-fixer \
		phpunit/phpunit
	@echo "✅ Dependências de desenvolvimento instaladas!"

# =============================================================================
# COMANDOS DOCKERIGNORE
# =============================================================================

dockerignore-dev: ## Usa .dockerignore para desenvolvimento
	@echo "Configurando para DESENVOLVIMENTO..."
	@cp $(DOCKERIGNORE_DEV) .dockerignore
	@echo "✅ .dockerignore configurado para desenvolvimento"

dockerignore-prod: ## Usa .dockerignore para produção
	@echo "Configurando para PRODUÇÃO..."
	@cp $(DOCKERIGNORE_PROD) .dockerignore
	@echo "✅ .dockerignore configurado para produção"

dockerignore-show: ## Mostra qual configuração está ativa
	@if [ -f .dockerignore ]; then \
		echo "Configuração atual do .dockerignore:"; \
		echo "====================================="; \
		head -n 5 .dockerignore; \
	else \
		echo "❌ .dockerignore não encontrado"; \
	fi

# =============================================================================
# COMANDOS DE BUILD ESPECÍFICOS
# =============================================================================

build-dev: dockerignore-dev build ## Build para desenvolvimento
	@echo "✅ Build de desenvolvimento completo"

build-prod: dockerignore-prod build ## Build para produção
	@echo "✅ Build de produção completo"

build-nc-dev: dockerignore-dev build-nc ## Rebuild completo para desenvolvimento
	@echo "✅ Rebuild de desenvolvimento completo (no cache)"

build-nc-prod: dockerignore-prod build-nc ## Rebuild completo para produção  
	@echo "✅ Rebuild de produção completo (no cache)"

# =============================================================================
# COMANDOS DE EMERGÊNCIA
# =============================================================================

# 🧨 Full Docker Cleanup: remove containers, images, volumes, networks, and prune system
doom:
	@echo "🧨 INICIANDO APOCALIPSE NOW..."
	@echo "🧩 Stopping all containers..."
	@docker stop $$(docker ps -aq) 2>/dev/null || true
	@echo "🗑️ Removing all containers..."
	@docker rm -f $$(docker ps -aq) 2>/dev/null || true
	@echo "🧱 Removing all images..."
	@docker rmi -f $$(docker images -aq) 2>/dev/null || true
	@echo "💾 Removing all volumes..."
	@docker volume rm $$(docker volume ls -q) 2>/dev/null || true
	@echo "🌐 Removing all networks..."
	@docker network rm $$(docker network ls -q | grep -vE '^(bridge|host|none)$$') 2>/dev/null || true
	@echo "🧹 Running Docker system prune..."
	@docker system prune -a --volumes -f
	@echo "✅ Full Docker cleanup complete!"
	@echo "🎉 Sistema limpo! Agora você pode recomeçar."

# =============================================================================
# DICA: Use 'make' sem argumentos para ver a ajuda
# =============================================================================
.DEFAULT_GOAL := help
