# Команды для выполнения на сервере

## 🎯 Для сервера dsc23ytp@dragon:~/laravel/public_html

**ВАЖНО:** На этом сервере используется `php8.2` вместо `php`

---

## 1️⃣ Установка Composer

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

---

## 2️⃣ Установка Node.js через nvm

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install --lts
nvm use --lts
nvm alias default node
node -v
npm -v
```

---

## 3️⃣ Переподключение по SSH

**ОБЯЗАТЕЛЬНО:** Переподключитесь для применения изменений PATH:

```bash
exit
ssh dsc23ytp@dragon
```

---

## 4️⃣ Проверка путей

```bash
which composer
which node
which npm
```

Запишите пути - они могут понадобиться для `.env`

---

## 5️⃣ Настройка проекта

```bash
cd ~/laravel/public_html

# Установка Composer зависимостей
composer install --no-dev --optimize-autoloader

# Установка npm зависимостей (если есть frontend)
cd frontend && npm install && cd ..
```

---

## 6️⃣ Настройка .env

Откройте `.env`:

```bash
nano .env
```

Добавьте/проверьте настройки:

```env
# Пути к инструментам (если нужно указать вручную)
# Актуальные пути на сервере (проверено):
NPM_PATH=/home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/npm
COMPOSER_PATH=/home/d/dsc23ytp/bin/composer

# Настройки деплоя
DEPLOY_SERVER_URL=https://your-server.com
DEPLOY_TOKEN=your-secret-token
GIT_REPOSITORY_URL=https://github.com/username/repo.git
```

Замените пути на те, что получили в шаге 4.

---

## 7️⃣ Проверка путей (ВАЖНО!)

```bash
which composer
which node
which npm
```

Запишите пути - они могут понадобиться для `.env`

**Ожидаемые пути:**
- Composer: `/home/d/dsc23ytp/bin/composer`
- Node.js: `/home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/node`
- npm: `/home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/npm`

## 8️⃣ Проверка Deploy

```bash
cd ~/laravel/public_html
php8.2 artisan deploy --dry-run
```

---

## ✅ Готово!

После выполнения всех шагов команда `php8.2 artisan deploy` будет работать корректно.

**Статус настройки:** См. `SERVER_STATUS.md` - все инструменты установлены и проверены ✅

---

## 📝 Полезные команды

```bash
# Проверка версий
php8.2 -v
composer -V
node -v
npm -v

# Проверка путей
which php8.2
which composer
which node
which npm

# Выполнение миграций
php8.2 artisan migrate

# Очистка кеша
php8.2 artisan config:clear
php8.2 artisan cache:clear

# Запуск деплоя
php8.2 artisan deploy
```

---

## 🔧 Если что-то не работает

### Composer не найден

```bash
export PATH="$HOME/bin:$PATH"
composer -V
```

### npm не найден

```bash
source ~/.nvm/nvm.sh
nvm use --lts
npm -v
```

### PHP команды

Всегда используйте `php8.2` вместо `php`:
- `php8.2 artisan ...`
- `php8.2 -v`
- `php8.2 composer-setup.php`

