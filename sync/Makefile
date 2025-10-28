# Makefile para SyncStock (PHP 8.4 + Firebird + MySQL)
# Comandos para build, execução, debug e controle do container

.PHONY: build build-fast up start run exec logs stop restart clean help

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================
CONTAINER = sync
SCRIPT    = src/sync.php
LOGFILE   = sincronizacao.log

# =============================================================================
# COMANDOS PRINCIPAIS
# =============================================================================

help: ## Mostra esta ajuda (padrão)
	@echo "Makefile para SyncStock"
	@echo ""
	@echo "Comandos disponíveis:"
	@echo "  make build       → Rebuild completo (com --no-cache)"
	@echo "  make build-fast  → Build rápido (usa cache)"
	@echo "  make up          → Sobe o container em background (-d)"
	@echo "  make start       → Sobe o container em foreground (sem -d)"
	@echo "  make run         → Executa o script de sincronização"
	@echo "  make exec        → Entra no container (bash)"
	@echo "  make logs        → Segue o log em tempo real"
	@echo "  make stop        → Para o container removendo orphans"
	@echo "  make restart     → Para + Rebuild + Sobe + Executa (sequência completa)"
	@echo "  make clean       → Remove container, volumes e limpa cache"
	@echo "  make help        → Mostra esta ajuda"
	@echo ""

build: ## Rebuild completo com --no-cache
	@echo "Building image (NO CACHE)..."
	docker compose build --no-cache

build-fast: ## Build rápido usando cache
	@echo "Building image (using cache)..."
	docker compose build

up: ## Sobe o container em background (-d)
	@echo "Starting container in background..."
	docker compose up -d

start: ## Sobe o container em foreground (sem -d)
	@echo "Starting container in foreground..."
	docker compose up

run: ## Executa o script de sincronização
	@echo "Executing $(SCRIPT)..."
	docker exec -it $(CONTAINER) php $(SCRIPT)

exec: ## Entra no container (bash interativo)
	@echo "Entering container $(CONTAINER)..."
	docker exec -it $(CONTAINER) bash

logs: ## Segue o log da sincronização
	@echo "Following $(LOGFILE)..."
	docker exec -it $(CONTAINER) tail -f $(LOGFILE)

stop: ## Para o container removendo containers órfãos
	@echo "Stopping containers and removing orphans..."
	docker compose down --remove-orphans

restart: stop build up run ## Para + Rebuild (com --no-cache) + Sobe + Executa
	@echo "Restart sequence completed!"

clean: ## Remove container, volumes e limpa imagens
	@echo "Cleaning up: removing containers, volumes, and pruning system..."
	docker compose down -v --remove-orphans
	docker system prune -f

# =============================================================================
# DICA: Use 'make' sem argumentos para ver a ajuda
# =============================================================================
.DEFAULT_GOAL := help
