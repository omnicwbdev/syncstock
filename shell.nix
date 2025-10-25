{ pkgs ? import <nixpkgs> {} }:

pkgs.mkShell {
  name = "php-dev-shell";

  buildInputs = with pkgs; [
    # PHP com extensões básicas (usando PHP 8.2)
    (php82.withExtensions ({ all, ... }: with all; [
      pdo_mysql
      mysqli
      pdo_pgsql
      redis
      sqlite3
      xdebug
    ]))
    
    php82Packages.composer
    # Adições para PDO Firebird
    firebird  # Biblioteca cliente do Firebird
    autoconf  # Necessário para phpize
    automake  # Ferramentas de build
    libtool   # Ferramentas de build
    gcc       # Compilador
    pkg-config  # Para localizar bibliotecas
    git
    gh
    openssh
    curl
    wget
    jq
    gnumake
    bashInteractive
    neovim
    go-task
    fzf
    silver-searcher
    fd
    bat
    eza
    ripgrep
    tmux
    htop
    html-tidy
    entr
    lnav
    httpie
    jless
    yq
    sops
    gnupg
    inotify-tools
    pv
    moreutils
    ranger
    tree
  ];

  env = {
    PHP_MEMORY_LIMIT = "2G";
    PHP_MAX_EXECUTION_TIME = "300";
    PHP_IDE_CONFIG = "serverName=localhost";
    COMPOSER_MEMORY_LIMIT = "-1";
    COMPOSER_ALLOW_SUPERUSER = "1";
    COMPOSER_PROCESS_TIMEOUT = "1800";
    COMPOSER_NO_INTERACTION = "1";
    COMPOSER_CACHE_DIR = "$PWD/.cache/composer";
    XDEBUG_MODE = "develop,debug,coverage";
    XDEBUG_CONFIG = "client_host=127.0.0.1 client_port=9003";
    # Caminhos para Firebird
    LD_LIBRARY_PATH = "${pkgs.firebird}/lib:${pkgs.lib.makeLibraryPath [pkgs.firebird]}";
    PKG_CONFIG_PATH = "${pkgs.firebird}/lib/pkgconfig:${pkgs.lib.makeSearchPath "lib/pkgconfig" [pkgs.firebird]}";
    CFLAGS = "-I${pkgs.firebird}/include";
    LDFLAGS = "-L${pkgs.firebird}/lib";
  };

  shellHook = ''
    export EDITOR="nvim"
    export NVIM_CONFIG_DIR="$HOME/.config/nvim"

    # Ensure project-local Composer cache directory exists
    mkdir -p "$PWD/.cache/composer"
    chmod 700 "$PWD/.cache/composer"

    # SSH Key setup (remains in $HOME)
    export NIX_SSH_KEY="$HOME/.ssh/nix_shell_id_ed25519"

    if [ ! -d "$HOME/.ssh" ]; then
      mkdir -p "$HOME/.ssh"
      chmod 700 "$HOME/.ssh"
    fi

    if [ ! -f "$NIX_SSH_KEY" ]; then
      echo "🔐 Generating persistent SSH key ed25519 at $NIX_SSH_KEY..."
      ssh-keygen -t ed25519 -C "nix-shell-generated-key" -f "$NIX_SSH_KEY" -N "" >/dev/null 2>&1
      chmod 600 "$NIX_SSH_KEY"
      chmod 644 "$NIX_SSH_KEY.pub"
      echo "✅ Key created: $NIX_SSH_KEY.pub"
      echo
      echo "📋 Copy this public key and add it to your GitHub account:"
      cat "$NIX_SSH_KEY.pub"
      echo
    else
      echo "🔁 Using existing persistent SSH key at $NIX_SSH_KEY"
    fi

    if [ -z "$SSH_AUTH_SOCK" ] || ! ssh-add -l >/dev/null 2>&1; then
      eval "$(ssh-agent -s)" >/dev/null 2>&1
    fi

    ssh-add -q "$NIX_SSH_KEY" 2>/dev/null || true
    export PATH="${pkgs.openssh}/bin:$PATH"
    export GIT_SSH_COMMAND="ssh -i $NIX_SSH_KEY -o IdentitiesOnly=yes -F /dev/null"

    SSH_OK=false
    if ! gh auth status >/dev/null 2>&1; then
      if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
        echo "🎉 SSH key works with GitHub!"
        echo | gh auth login --hostname github.com --ssh >/dev/null 2>&1 && echo "✅ GitHub CLI logged in via SSH"
        SSH_OK=true
      else
        echo -e "\e[41;97m ⚠️ SSH key not added to GitHub! Copy $NIX_SSH_KEY.pub to GitHub ⚠️ \e[0m"
      fi
    else
      SSH_OK=true
    fi

    # Advanced Git configuration
    echo "⚙️ Setting up advanced Git configuration..."
    git config --global alias.st status
    git config --global alias.br branch
    git config --global alias.co checkout
    git config --global alias.sw switch
    git config --global alias.cm commit
    git config --global alias.psh push
    git config --global alias.pl pull
    git config --global alias.df diff
    git config --global alias.lg "log --oneline --graph --decorate -20"
    git config --global alias.unstage "reset HEAD --"
    git config --global alias.last "log -1 HEAD"
    git config --global alias.brf "branch --list | fzf | xargs git switch"
    git config --global alias.cleanup "!git fetch --prune && git branch -vv | grep ': gone]' | awk '{print \$1}' | xargs -r git branch -D"
    git config --global alias.wip "!git add -A && git commit -m 'WIP'"
    git config --global core.editor "nvim"
    git config --global init.defaultBranch "main"
    git config --global pull.rebase false

    # Advanced FZF configuration
    export FZF_DEFAULT_OPTS="--height 40% --layout=reverse --border --preview-window=right:60%"
    export FZF_DEFAULT_COMMAND="fd --type f --hidden --follow --exclude .git"

    # Useful PHP development aliases
    alias phpunit="./vendor/bin/phpunit"
    alias pest="./vendor/bin/pest"
    alias pint="./vendor/bin/pint"
    alias phpstan="./vendor/bin/phpstan"
    alias psalm="./vendor/bin/psalm"
    alias html-lint="tidy -errors -quiet"

    alias nv="nvim"
    alias lz="lazygit"

    # Helper functions
    php-server() {
      local port=''${1:-8000}
      php -S localhost:$port -t public/
    }

    composer-update-all() {
      composer update --with-dependencies --optimize-autoloader
    }

    # Função para instalar PDO Firebird
    install-firebird-extension() {
      echo "📦 Installing Firebird PDO extension via PECL..."
      
      # Verificar se pecl está disponível
      if ! command -v pecl &> /dev/null; then
        echo "❌ PECL not available. Ensure php82Packages.pecl or equivalent is installed."
        return 1
      fi

      # Verificar se as bibliotecas do Firebird estão disponíveis
      FIREBIRD_LIB="${pkgs.firebird}/lib/libfbclient.so"
      if [ -f "$FIREBIRD_LIB" ]; then
        echo "✅ Found libfbclient.so at $FIREBIRD_LIB"
      else
        echo "❌ Firebird client library (libfbclient.so) not found at $FIREBIRD_LIB"
        echo "💡 Ensure the 'firebird' package is correctly installed or try updating Nixpkgs."
        return 1
      fi

      # Verificar headers do Firebird
      FIREBIRD_INCLUDE="${pkgs.firebird}/include"
      if [ -f "$FIREBIRD_INCLUDE/ibase.h" ]; then
        echo "✅ Found Firebird headers at $FIREBIRD_INCLUDE"
      else
        echo "❌ Firebird headers (ibase.h) not found at $FIREBIRD_INCLUDE"
        return 1
      fi
      
      # Atualizar canal PECL
      echo "🔄 Updating PECL channel..."
      pecl channel-update pecl.php.net || {
        echo "❌ Failed to update PECL channel"
        return 1
      }
      
      # Tentar instalar uma versão específica do pdo_firebird
      echo "🔨 Building pdo_firebird-0.9.1 extension..."
      CFLAGS="-I${pkgs.firebird}/include" LDFLAGS="-L${pkgs.firebird}/lib" pecl install pdo_firebird-0.9.1 || {
        echo "❌ Failed to install PDO Firebird extension via PECL"
        echo "💡 Check if Firebird client libraries and headers are correctly installed."
        return 1
      }
      
      # Habilitar a extensão no PHP
      PHP_EXT_DIR=$(php -i | grep '^extension_dir' | awk '{print $3}')
      if [ -f "$PHP_EXT_DIR/pdo_firebird.so" ]; then
        echo "extension=pdo_firebird.so" > $(php --ini | grep "Loaded Configuration File" | awk '{print $NF}' | sed 's/php.ini$/conf.d/pdo_firebird.ini/')
        echo "✅ PDO Firebird extension installed and enabled successfully"
      else
        echo "❌ pdo_firebird.so not found in $PHP_EXT_DIR"
        return 1
      fi
      
      # Verificar se a extensão está carregada
      if php -m | grep -q pdo_firebird; then
        echo "✅ PDO Firebird extension is loaded"
      else
        echo "⚠️ PDO Firebird extension installed but not loaded. Check PHP configuration."
        return 1
      fi
    }

    # Tentar instalar pdo_firebird automaticamente
    if ! php -m | grep -q pdo_firebird; then
      echo "⚠️ PDO Firebird extension not found. Attempting to install..."
      install-firebird-extension
    else
      echo "✅ PDO Firebird extension already loaded"
    fi

    # Configure Composer for optimal performance
    echo "⚡ Configuring Composer for optimal performance..."
    composer config --global process-timeout 1800
    composer config --global discard-changes true
    composer config --global sort-packages true
    composer config --global optimize-autoloader true

    # Set environment variable for Composer
    export COMPOSER_DISCARD_CHANGES=true

    # Improved Git functions
    parse_git_branch() {
      git branch 2>/dev/null | grep '^\*' | cut -d' ' -f2-
    }

    git_status_symbol() {
      if git rev-parse --git-dir >/dev/null 2>&1; then
        if git diff --quiet --cached 2>/dev/null && git diff --quiet 2>/dev/null; then
          echo "\[\e[32m\]✔\[\e[0m\]"
        else
          echo "\[\e[31m\]✗\[\e[0m\]"
        fi
      else
        echo ""
      fi
    }

    set_ps1() {
      local current_dir="''${PWD##*/}"
      local branch=""
      local status=""
      
      if git rev-parse --git-dir >/dev/null 2>&1; then
        branch=$(parse_git_branch)
        status=$(git_status_symbol)
      fi
      
      if [ -n "$branch" ]; then
        if [ "$SSH_OK" = true ]; then
          PS1="\[\e[32m\][php82-dev]\[\e[0m\]:~/$current_dir \[\e[34m\]$branch\[\e[0m\] $status\$ "
        else
          PS1="\[\e[32m\][php82-dev]\[\e[0m\]:~/$current_dir \[\e[31m\]NO SSH KEY!\[\e[0m\]\$ "
        fi
      else
        PS1="\[\e[32m\][php82-dev]\[\e[0m\]:~/$current_dir \$ "
      fi
    }

    export PROMPT_COMMAND="set_ps1"

    # Service initialization
    echo "🐳 Starting development services..."
    
    # Start Redis if not running
    if ! pgrep redis-server > /dev/null; then
      redis-server --daemonize yes
      echo "✅ Redis started"
    fi

    # Verificar extensões PHP instaladas
    echo "🔍 Checking PHP extensions..."
    php -m | grep -E 'pdo|mysql|sqlite|firebird' | sort

    echo
    echo "🚀 PHP 8.2 Development Environment Ready!"
    echo "   - PHP: $(php -v 2>/dev/null | head -1)"
    echo "   - Composer: $(composer --version 2>/dev/null | head -1)"
    echo
    echo "⚠️  Firebird PDO Extension Note:"
    echo "   The PDO Firebird extension is installed via PECL in this shell."
    echo "   If you encounter issues, ensure Firebird client libraries are available."
    echo "   You can retry installation with: install-firebird-extension"
    echo
    echo "📝 Useful commands:"
    echo "   - php-server      - Start PHP development server"
    echo "   - composer-update-all - Update Composer dependencies"
    echo "   - install-firebird-extension - Attempt to install Firebird extension"
    echo
  '';
}
