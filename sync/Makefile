# Makefile para SyncStock (PHP 8.4 + Firebird + MySQL)
# Comandos para build, execução, debug e controle do container

.PHONY: build build-fast up start run exec logs stop restart clean help dangling

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================
CONTAINER = sync
SCRIPT    = src/sync.php
LOGFILE   = sincronizacao.log
IMAGE_NAME = syncstock/sync

# =============================================================================
# COMANDOS PRINCIPAIS
# =============================================================================

help: ## Mostra esta ajuda (padrão)
	@echo "Makefile para SyncStock"
	@echo ""
	@echo "Comandos disponíveis:"
	@echo "  make build        → Build da imagem (latest)"
	@echo "  make build-fast   → Build rápido usando cache"
	@echo "  make build-nc     → Rebuild completo (com --no-cache)"
	@echo "  make up           → Sobe o container em background (-d)"
	@echo "  make start        → Sobe o container em foreground (sem -d)"
	@echo "  make run          → Executa o script de sincronização"
	@echo "  make exec         → Entra no container (bash)"
	@echo "  make logs         → Segue o log em tempo real"
	@echo "  make stop         → Para o container removendo orphans"
	@echo "  make restart      → Para + Build + Sobe + Executa"
	@echo "  make clean        → Remove container, volumes e limpa cache"
	@echo "  make dangling     → Remove imagens dangling (sem tag)"
	@echo "  make help         → Mostra esta ajuda"
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
# DICA: Use 'make' sem argumentos para ver a ajuda
# =============================================================================
.DEFAULT_GOAL := help
