# 🚀 Быстрая настройка сервера для Deploy

## Что нужно выполнить на сервере

**ВАЖНО:** На этом сервере используется `php8.2` вместо `php`

### 1️⃣ Установка Composer

```bash
cd ~
php8.2 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php8.2 composer-setup.php
rm composer-setup.php
mkdir -p ~/bin
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
composer -V
```

### 2️⃣ Установка Node.js через nvm

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install --lts
nvm use --lts
nvm alias default node
node -v
npm -v
```

### 3️⃣ Переподключение по SSH

**ВАЖНО:** Переподключитесь по SSH для применения изменений PATH:
```bash
exit
ssh login@server.beget.com
```

### 4️⃣ Проверка путей

```bash
which composer
which node
which npm
```

Запишите пути - они могут понадобиться для `.env`

### 5️⃣ Настройка проекта

```bash
cd ~/laravel/public_html
composer install --no-dev --optimize-autoloader
cd frontend && npm install && cd ..
```

### 6️⃣ Настройка .env (опционально)

Если автоматическое определение не работает, добавьте в `.env`:

```env
NPM_PATH=/home/login/.nvm/versions/node/v20.10.0/bin/npm
COMPOSER_PATH=/home/login/bin/composer
DEPLOY_SERVER_URL=https://your-server.com
DEPLOY_TOKEN=your-secret-token
```

### 7️⃣ Проверка Deploy

```bash
php8.2 artisan deploy --dry-run
```

---

## ✅ Готово!

После выполнения всех шагов команда `php8.2 artisan deploy` будет работать корректно.

**Подробная инструкция:** см. `SERVER_SETUP.md`  
**Команды для вашего сервера:** см. `SERVER_COMMANDS.md`

