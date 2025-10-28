# Makefile para SyncStock (PHP 8.4 + Firebird + MySQL)
# Comandos para build, execução, debug e controle do container

.PHONY: build build-fast up start run exec logs stop restart clean help version tag

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================
CONTAINER = sync
SCRIPT    = src/sync.php
LOGFILE   = sincronizacao.log
IMAGE_NAME = syncstock/sync
VERSION_FILE = VERSION.txt

# =============================================================================
# CONTROLE DE VERSÃO
# =============================================================================

# Função para ler a versão base do VERSION.txt
define get_base_version
$(shell if [ -f "$(VERSION_FILE)" ]; then cat "$(VERSION_FILE)"; else echo "0.1.0"; fi)
endef

# Função para obter a última tag do Git
define get_last_tag
$(shell git describe --tags --abbrev=0 2>/dev/null || echo "0.0.0")
endef

# Função para incrementar patch baseado na última tag
define increment_patch
$(shell BASE_VERSION=$(call get_base_version); \
         LAST_TAG=$(call get_last_tag); \
         # Se a última tag não começa com a base version, reset counter \
         if [ "$${LAST_TAG%.*}" != "$$BASE_VERSION" ]; then \
             echo "$$BASE_VERSION.0"; \
         else \
             PATCH=$$(echo $$LAST_TAG | cut -d. -f3); \
             NEW_PATCH=$$((PATCH + 1)); \
             echo "$$BASE_VERSION.$$NEW_PATCH"; \
         fi)
endef

# =============================================================================
# COMANDOS PRINCIPAIS
# =============================================================================

help: ## Mostra esta ajuda (padrão)
	@echo "Makefile para SyncStock"
	@echo ""
	@echo "Comandos disponíveis:"
	@echo "  make version      → Mostra a versão base e próxima build"
	@echo "  make tag          → Cria uma nova tag Git com versão incrementada"
	@echo "  make build        → Build com versão automática (PATCH increment)"
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
	@echo "  make help         → Mostra esta ajuda"
	@echo ""
	@echo "CONTROLE DE VERSÃO:"
	@echo "  - Versão base: edit $(VERSION_FILE) (ex: 0.1.0)"
	@echo "  - Cada build: incrementa PATCH (ex: 0.1.1, 0.1.2, ...)"
	@echo "  - Mudar MAJOR/MINOR: edite $(VERSION_FILE)"
	@echo ""

version: ## Mostra a versão base e próxima build
	@echo "Versão base ($(VERSION_FILE)): $(call get_base_version)"
	@echo "Última tag: $(call get_last_tag)"
	@echo "Próximo build: $(call increment_patch)"
	@echo "Imagem: $(IMAGE_NAME):$(call increment_patch)"

tag: ## Cria uma nova tag Git com versão incrementada
	@NEW_VERSION=$(call increment_patch); \
	echo "Criando tag: $$NEW_VERSION"; \
	git tag -a $$NEW_VERSION -m "Build $$NEW_VERSION"; \
	echo "Tag $$NEW_VERSION criada! Use 'git push --tags' para enviar."

build: ## Build com incremento automático de PATCH (recomendado)
	@NEW_VERSION=$(call increment_patch); \
	echo "Building $(IMAGE_NAME):$$NEW_VERSION..."; \
	docker compose build --build-arg APP_VERSION=$$NEW_VERSION; \
	docker tag $(IMAGE_NAME):latest $(IMAGE_NAME):$$NEW_VERSION; \
	echo "Build completo! Versão: $$NEW_VERSION"

build-fast: ## Build rápido usando cache (sem versão)
	@echo "Building $(IMAGE_NAME):latest (using cache)..."
	@docker compose build

build-nc: ## Rebuild completo com --no-cache
	@NEW_VERSION=$(call increment_patch); \
	echo "Building $(IMAGE_NAME):$$NEW_VERSION (NO CACHE)..."; \
	docker compose build --no-cache --build-arg APP_VERSION=$$NEW_VERSION; \
	docker tag $(IMAGE_NAME):latest $(IMAGE_NAME):$$NEW_VERSION; \
	echo "Build completo! Versão: $$NEW_VERSION"

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

# =============================================================================
# DICA: Use 'make' sem argumentos para ver a ajuda
# =============================================================================
.DEFAULT_GOAL := help

