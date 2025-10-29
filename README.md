[![Dependabot Updates](https://github.com/omnicwbdev/syncstock/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/omnicwbdev/syncstock/actions/workflows/dependabot/dependabot-updates)
[![Docker Image CI](https://github.com/omnicwbdev/syncstock/actions/workflows/docker-ci.yaml/badge.svg)](https://github.com/omnicwbdev/syncstock/actions/workflows/docker-ci.yaml)
[![PHP Composer](https://github.com/omnicwbdev/syncstock/actions/workflows/php.yaml/badge.svg)](https://github.com/omnicwbdev/syncstock/actions/workflows/php.yaml)


# SyncStock

Repositório com duas partes relacionadas ao processo de sincronização e sua interface web:

- `sync/` — Aplicação CLI / serviços Docker para executar os processos de sincronização.
- `sync-web/` — Interface web em PHP para gerenciar/agendar cron jobs e visualizar agendamentos.

## Estrutura do repositório

- `sync/`
  - `composer.json`, `docker-compose.yaml`
  - `Dockerfile.php7.4`, `Dockerfile.php8.2`, `Dockerfile.php8.4`
  - `src/sync.php` (ponto de entrada da aplicação de sincronização)
- `sync-web/`
  - `composer.json`, `php.ini`, `sync-wrapper.sh`
  - `public/index.php` (front controller)
  - `src/` (classe `GerenciadorCrontab.php`, validadores, etc.)
  - `templates/` (interfaces de gerenciamento de crontab)

> Observação: cada subprojeto (`sync` e `sync-web`) possui seu próprio README com informações específicas (veja `sync/README.md` e `sync-web/README.md`). Este README dá uma visão geral e passos rápidos para começar.

## Requisitos

- Docker & Docker Compose (recomendado para ambiente consistente)
- PHP (para executar `sync-web` localmente sem Docker, opcional)
- Composer (para instalar dependências PHP quando necessário)

## Início rápido (Docker)

A maneira mais simples de levantar os serviços de sincronização é usando o `docker-compose` que está dentro da pasta `sync/`.

1. Abra um terminal na raiz do repositório e entre em `sync/`:

```bash
cd sync
```

2. Suba os containers (build se necessário):

```bash
docker-compose up -d --build
```

Isso irá construir/rodar os serviços definidos em `sync/docker-compose.yaml` utilizando os Dockerfiles disponíveis.

## Executando a interface web localmente (rápido, sem Docker)

Se preferir testar rapidamente a interface web sem usar Docker, você pode usar o servidor embutido do PHP apontando para a pasta `sync-web/public`:

```bash
cd sync-web
# iniciar o servidor embutido (usa PHP instalado localmente)
php -S localhost:8000 -t public
```

Depois, abra `http://localhost:8000` no seu navegador.

## Uso resumo

- A parte `sync/` contém a lógica de sincronização e está preparada para rodar em containers com PHP em diferentes versões (veja `Dockerfile.php*`).
- A parte `sync-web/` provê páginas para criar/editar agendamentos (crontab), validadores e wrappers auxiliares (`sync-wrapper.sh`).

Para ações específicas (ex.: configurar credenciais, parâmetros de sincronização ou agendamento de jobs), consulte os READMEs e arquivos de configuração dentro de cada subpasta.

## Desenvolvimento

- Instale dependências PHP quando necessário:

```bash
# exemplo genérico, rode dentro das pastas que contêm composer.json
cd sync-web
composer install
```

- Para debugar ou desenvolver, use o servidor embutido do PHP ou rode containers com imagens que exponham Xdebug (se configurado).

## Testes

Este repositório não contém uma suíte de testes automatizados evidente no nível raiz. Se você adicionar testes, prefira PHPUnit para partes PHP e documente os comandos aqui.

## Contribuição

1. Fork do repositório
2. Crie uma branch com a sua feature/bugfix
3. Abra um Pull Request descrevendo a mudança

Siga as convenções de código presentes no projeto e adicione testes quando alterar lógica crítica.

## Licença

Adicione aqui a licença do projeto (se aplicável). Se não houver uma licença no repositório, considere adicionar um arquivo `LICENSE`.

## Contato

Para dúvidas sobre o projeto, abra uma issue neste repositório ou contate os mantenedores listados no arquivo `composer.json` de cada subprojeto.

---

README gerado automaticamente em Português. Se quiser que eu inclua seções adicionais (ex.: exemplos de configuração, variáveis de ambiente, passos do `Makefile`, ou conteúdo extra das pastas `sync/` e `sync-web/`), diga o que quer que eu detalhe e eu atualizo o arquivo.
