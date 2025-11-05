# =============================================================================
# Makefile para SyncStock (PHP 8.4 + Firebird + MySQL)
# =============================================================================

.PHONY: build build-fast build-nc up start run exec logs stop restart clean help dangling \
        lint lint-fix analyse test debug shell nginx nginx-stop \
        composer-install composer-update dev-deps \
        dockerignore-dev dockerignore-prod dockerignore-show \
        build-dev build-prod build-nc-dev build-nc-prod doom \
        nuke nuke-safe nuke-images nuke-containers nuke-volumes

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================
CONTAINER        := $(strip sync)
SCRIPT           := $(strip src/sync.php)
LOGFILE          := $(strip sincronizacao.log)
IMAGE_NAME       := $(strip syncstock/sync)
SRC_DIR          := $(strip src/)
DOCKERIGNORE_DEV := .dockerignore.dev
DOCKERIGNORE_PROD := .dockerignore.prod

# =============================================================================
# AJUDA
# =============================================================================
.DEFAULT_GOAL := help

help:
	@echo "Makefile para SyncStock"
	@echo ""
	@echo "COMANDOS PRINCIPAIS:"
	@echo "  make build           → Build da imagem (latest)"
	@echo "  make build-nc        → Build sem cache"
	@echo "  make up/start        → Sobe o container (-d ou foreground)"
	@echo "  make run             → Executa o script de sincronização"
	@echo "  make exec/shell      → Entra no container"
	@echo "  make logs            → Mostra logs em tempo real"
	@echo "  make stop/restart    → Para e reinicia containers"
	@echo "  make clean/dangling  → Limpeza leve de containers e imagens"
	@echo ""
	@echo "COMANDOS DE DESENVOLVIMENTO:"
	@echo "  make lint/lint-fix   → Code Sniffer (análise e correção)"
	@echo "  make analyse/test    → PHPStan e PHPUnit"
	@echo "  make debug           → Execução com Xdebug"
	@echo "  make composer-*      → Gerência de dependências"
	@echo ""
	@echo "DOCKERIGNORE / BUILD:"
	@echo "  make dockerignore-dev|prod → Alterna .dockerignore"
	@echo "  make build-dev|prod        → Build de ambiente"
	@echo "  make build-nc-dev|prod     → Build sem cache"
	@echo ""
	@echo "LIMPEZA AVANÇADA:"
	@echo "  make nuke*, doom → Limpezas completas e destrutivas"
	@echo ""

# =============================================================================
# BUILD / EXECUÇÃO
# =============================================================================

build: ## Build padrão (usa cache)
	@echo "🚧 Building $(IMAGE_NAME):latest..."
	@docker compose build

build-fast: build ## Build rápido (mantido para compatibilidade)
	@true

build-nc: ## Build completo sem cache
	@echo "🧱 Rebuilding $(IMAGE_NAME):latest (no cache)..."
	@docker compose build --no-cache
	@echo "✅ Build completo!"

up: ## Sobe container em background
	@echo "🚀 Subindo container em background..."
	@docker compose up -d

start: ## Sobe container em foreground
	@echo "🚀 Subindo container em foreground..."
	@docker compose up

run: ## Executa script principal
	@echo "▶️ Executando $(SCRIPT)..."
	@docker exec -it $(CONTAINER) php $(SCRIPT)

exec shell: ## Entra no container
	@echo "💻 Entrando no container $(CONTAINER)..."
	@docker exec -it $(CONTAINER) bash

logs: ## Mostra logs
	@echo "📜 Logs: $(LOGFILE)"
	@docker exec -it $(CONTAINER) tail -f $(LOGFILE)

stop: ## Para containers e remove órfãos
	@echo "🛑 Parando containers..."
	@docker compose down --remove-orphans

restart: stop build up run ## Reinicia todo ciclo
	@echo "🔁 Restart completo!"

clean: ## Limpa containers, volumes e cache
	@echo "🧹 Limpando containers, volumes e cache..."
	@docker compose down -v --remove-orphans
	@docker system prune -f

dangling: ## Remove imagens <none>:<none>
	@echo "🧽 Removendo imagens dangling..."
	@DANGLING=$$(docker images -f "dangling=true" -q); \
	[ -n "$$DANGLING" ] && docker rmi $$DANGLING -f || echo "Nenhuma imagem dangling encontrada."

# =============================================================================
# DESENVOLVIMENTO
# =============================================================================

lint:
	@docker exec -it $(CONTAINER) phpcs --standard=PSR12 $(SRC_DIR)

lint-fix:
	@docker exec -it $(CONTAINER) phpcbf --standard=PSR12 $(SRC_DIR)

analyse:
	@docker exec -it $(CONTAINER) phpstan analyse $(SRC_DIR) --level=8

test:
	@docker exec -it $(CONTAINER) ./vendor/bin/phpunit

debug:
	@docker exec -it $(CONTAINER) php -d xdebug.mode=debug $(SCRIPT)

nginx:
	@docker compose -f docker-compose.yml up -d nginx
	@echo "🌐 Nginx disponível em http://localhost:8080"

nginx-stop:
	@docker compose -f docker-compose.yml stop nginx

composer-install:
	@docker exec -it $(CONTAINER) composer install --no-interaction --optimize-autoloader

composer-update:
	@docker exec -it $(CONTAINER) composer update --no-interaction --optimize-autoloader

dev-deps:
	@docker exec -it $(CONTAINER) composer require --dev \
		squizlabs/php_codesniffer phpstan/phpstan friendsofphp/php-cs-fixer phpunit/phpunit
	@echo "✅ Dependências de desenvolvimento instaladas!"

# =============================================================================
# DOCKERIGNORE / BUILDS
# =============================================================================

dockerignore-dev:
	@cp $(DOCKERIGNORE_DEV) .dockerignore && echo "✅ .dockerignore configurado para DEV"

dockerignore-prod:
	@cp $(DOCKERIGNORE_PROD) .dockerignore && echo "✅ .dockerignore configurado para PROD"

dockerignore-show:
	@if [ -f .dockerignore ]; then \
		echo "🧾 .dockerignore atual:"; head -n 5 .dockerignore; \
	else echo "❌ .dockerignore não encontrado"; fi

build-dev: dockerignore-dev build
	@echo "✅ Build DEV completo"

build-prod: dockerignore-prod build
	@echo "✅ Build PROD completo"

build-nc-dev: dockerignore-dev build-nc
	@echo "✅ Build DEV (no cache) completo"

build-nc-prod: dockerignore-prod build-nc
	@echo "✅ Build PROD (no cache) completo"

# =============================================================================
# LIMPEZA AVANÇADA
# =============================================================================

nuke:
	@echo "💣 REMOVENDO TUDO (containers, imagens, volumes)..."
	@read -p "Digite 'NUKE' para confirmar: " c && [ "$$c" = "NUKE" ] || exit 1
	@docker stop $$(docker ps -aq) 2>/dev/null || true
	@docker rm $$(docker ps -aq) 2>/dev/null || true
	@docker rmi $$(docker images -q) -f 2>/dev/null || true
	@docker volume rm $$(docker volume ls -q) 2>/dev/null || true
	@docker network prune -f
	@echo "✅ Docker limpo."

nuke-safe:
	@docker stop $$(docker ps -aq) 2>/dev/null || true
	@docker rm $$(docker ps -aq) 2>/dev/null || true
	@docker volume prune -f
	@docker network prune -f
	@docker image prune -f
	@echo "✅ Limpeza segura completa."

nuke-images:
	@read -p "Remover todas as imagens? (s/N): " c && [ "$$c" = "s" ] || exit 1
	@docker rmi $$(docker images -q) -f 2>/dev/null || true
	@echo "✅ Todas as imagens removidas."

nuke-containers:
	@docker stop $$(docker ps -aq) 2>/dev/null || true
	@docker rm $$(docker ps -aq) 2>/dev/null || true
	@echo "✅ Containers removidos."

nuke-volumes:
	@read -p "Apagar todos os volumes? (s/N): " c && [ "$$c" = "s" ] || exit 1
	@docker volume rm $$(docker volume ls -q) 2>/dev/null || true
	@echo "✅ Volumes removidos."

doom:
	@echo "🧨 APOCALIPSE NOW..."
	@docker stop $$(docker ps -aq) 2>/dev/null || true
	@docker rm -f $$(docker ps -aq) 2>/dev/null || true
	@docker rmi -f $$(docker images -aq) 2>/dev/null || true
	@docker volume rm $$(docker volume ls -q) 2>/dev/null || true
	@docker network rm $$(docker network ls -q | grep -vE '^(bridge|host|none)$$') 2>/dev/null || true
	@docker system prune -a --volumes -f
	@echo "🎉 Sistema Docker limpo e resetado!"

