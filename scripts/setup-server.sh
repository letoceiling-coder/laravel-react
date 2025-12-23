#!/bin/bash

# Скрипт автоматической настройки сервера для деплоя
# Использование: ./setup-server.sh
# 
# Этот скрипт настраивает:
# - Composer (глобально для пользователя)
# - Node.js + npm (через nvm)

set -e  # Остановка при ошибке

echo "🚀 Начало настройки сервера для деплоя..."
echo ""

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Функция для вывода сообщений
info() {
    echo -e "${GREEN}ℹ️  $1${NC}"
}

warn() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Проверка, что мы в домашней директории
cd ~

# ============================================
# 1. Установка Composer
# ============================================
info "Шаг 1: Установка Composer..."

if [ -f ~/bin/composer ]; then
    warn "Composer уже установлен в ~/bin/composer"
else
    # Создаём папку для бинарников
    mkdir -p ~/bin
    
    # Скачиваем Composer
    info "Скачивание Composer..."
    # Определяем команду PHP (php8.2, php8.1, php и т.д.)
    PHP_CMD="php"
    if command -v php8.2 &> /dev/null; then
        PHP_CMD="php8.2"
    elif command -v php8.1 &> /dev/null; then
        PHP_CMD="php8.1"
    fi
    
    info "Используется: $PHP_CMD"
    $PHP_CMD -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP_CMD composer-setup.php
    rm composer-setup.php
    
    # Перемещаем в bin
    if [ -f composer.phar ]; then
        mv composer.phar ~/bin/composer
        chmod +x ~/bin/composer
        info "✅ Composer установлен в ~/bin/composer"
    else
        error "Не удалось скачать Composer"
        exit 1
    fi
fi

# ============================================
# 2. Добавление ~/bin в PATH
# ============================================
info "Шаг 2: Настройка PATH для Composer..."

if [ -f ~/.bashrc ]; then
    if ! grep -q 'export PATH="$HOME/bin:$PATH"' ~/.bashrc; then
        echo '' >> ~/.bashrc
        echo '# Composer path' >> ~/.bashrc
        echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
        info "✅ PATH добавлен в .bashrc"
    else
        warn "PATH уже настроен в .bashrc"
    fi
else
    warn ".bashrc не найден, создаём новый"
    echo 'export PATH="$HOME/bin:$PATH"' > ~/.bashrc
fi

# Применяем изменения
export PATH="$HOME/bin:$PATH"

# Проверка Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer -V 2>&1 | head -n 1)
    info "✅ Composer работает: $COMPOSER_VERSION"
else
    warn "Composer не найден в PATH, но файл существует в ~/bin/composer"
    warn "Переподключитесь по SSH для применения изменений"
fi

# ============================================
# 3. Установка nvm
# ============================================
info "Шаг 3: Установка nvm (Node Version Manager)..."

if [ -d ~/.nvm ]; then
    warn "nvm уже установлен"
else
    info "Скачивание и установка nvm..."
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
    
    # Загружаем nvm в текущую сессию
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    
    info "✅ nvm установлен"
fi

# Загружаем nvm если он уже установлен
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# ============================================
# 4. Установка Node.js (LTS)
# ============================================
info "Шаг 4: Установка Node.js (LTS версия)..."

if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    warn "Node.js уже установлен: $NODE_VERSION"
else
    info "Установка Node.js LTS через nvm..."
    nvm install --lts
    nvm use --lts
    nvm alias default node  # Устанавливаем как версию по умолчанию
    info "✅ Node.js установлен"
fi

# Проверка Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    NPM_VERSION=$(npm -v)
    info "✅ Node.js: $NODE_VERSION"
    info "✅ npm: $NPM_VERSION"
else
    error "Node.js не найден после установки"
    exit 1
fi

# ============================================
# 5. Настройка npm PATH
# ============================================
info "Шаг 5: Настройка PATH для npm..."

NPM_PREFIX=$(npm config get prefix)
if [ -n "$NPM_PREFIX" ]; then
    NPM_BIN_PATH="$NPM_PREFIX/bin"
    
    if [ -f ~/.bashrc ]; then
        if ! grep -q "export PATH=\"\$PATH:\$(npm config get prefix)/bin\"" ~/.bashrc; then
            echo '' >> ~/.bashrc
            echo '# npm path' >> ~/.bashrc
            echo 'export PATH="$PATH:$(npm config get prefix)/bin"' >> ~/.bashrc
            info "✅ npm PATH добавлен в .bashrc"
        else
            warn "npm PATH уже настроен в .bashrc"
        fi
    fi
    
    # Добавляем в текущую сессию
    export PATH="$PATH:$NPM_BIN_PATH"
fi

# ============================================
# 6. Финальная проверка
# ============================================
echo ""
info "=========================================="
info "Финальная проверка установки:"
info "=========================================="

# Composer
if command -v composer &> /dev/null; then
    COMPOSER_PATH=$(which composer)
    COMPOSER_VERSION=$(composer -V 2>&1 | head -n 1)
    echo -e "${GREEN}✅ Composer:${NC} $COMPOSER_PATH"
    echo -e "   $COMPOSER_VERSION"
else
    echo -e "${YELLOW}⚠️  Composer: не найден в PATH${NC}"
    echo -e "   Файл: ~/bin/composer"
fi

# Node.js
if command -v node &> /dev/null; then
    NODE_PATH=$(which node)
    NODE_VERSION=$(node -v)
    echo -e "${GREEN}✅ Node.js:${NC} $NODE_PATH"
    echo -e "   Версия: $NODE_VERSION"
else
    echo -e "${RED}❌ Node.js: не найден${NC}"
fi

# npm
if command -v npm &> /dev/null; then
    NPM_PATH=$(which npm)
    NPM_VERSION=$(npm -v)
    echo -e "${GREEN}✅ npm:${NC} $NPM_PATH"
    echo -e "   Версия: $NPM_VERSION"
else
    echo -e "${RED}❌ npm: не найден${NC}"
fi

echo ""
info "=========================================="
info "Настройка завершена!"
info "=========================================="
echo ""
warn "ВАЖНО: Переподключитесь по SSH для применения всех изменений PATH"
echo ""
info "Для проверки выполните:"
echo "  composer -V"
echo "  node -v"
echo "  npm -v"
echo ""

