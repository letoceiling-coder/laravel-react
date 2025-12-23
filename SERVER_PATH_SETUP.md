# Настройка PATH для глобального доступа к composer и npm

## Проблема

При выполнении через веб-сервер (PHP-FPM) переменные окружения из `.bashrc` не загружаются автоматически. Поэтому `composer` и `npm` не находятся в PATH.

## Решение

### Вариант 1: Настройка PATH в .bashrc (для SSH)

Выполни на сервере:

```bash
cd ~
nano .bashrc
```

Добавь в конец файла:

```bash
# Composer и npm в PATH
export PATH="$HOME/bin:$PATH"

# NVM (если используется)
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"

# Добавляем путь к Node.js в PATH
export PATH="$HOME/.nvm/versions/node/$(nvm current)/bin:$PATH"
```

Примени изменения:

```bash
source ~/.bashrc
```

Проверь:

```bash
which composer
which npm
which node
```

### Вариант 2: Настройка через .env (рекомендуется)

Добавь в `.env` на сервере:

```env
COMPOSER_PATH=/home/d/dsc23ytp/bin/composer
NPM_PATH=/home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/npm
```

После этого выполни:

```bash
cd ~/laravel/public_html
php8.2 artisan config:clear
```

### Вариант 3: Создание симлинков (если есть доступ)

Если у тебя есть доступ к `/usr/local/bin`:

```bash
ln -s /home/d/dsc23ytp/bin/composer /usr/local/bin/composer
ln -s /home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/npm /usr/local/bin/npm
ln -s /home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/node /usr/local/bin/node
```

## Проверка

После настройки проверь:

```bash
# В SSH
composer --version
npm --version
node --version

# В Laravel (через tinker)
cd ~/laravel/public_html
php8.2 artisan tinker
>>> exec('which composer')
>>> exec('which npm')
```

## Важно

- Для веб-сервера (PHP-FPM) переменные из `.bashrc` **не загружаются**
- Используй **Вариант 2** (через `.env`) для работы через веб-сервер
- Код автоматически найдет composer и npm по указанным путям

## Текущие пути на сервере

Согласно настройкам:

- **Composer**: `/home/d/dsc23ytp/bin/composer`
- **Node.js**: `/home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/node`
- **npm**: `/home/d/dsc23ytp/.nvm/versions/node/v24.12.0/bin/npm`

Эти пути уже настроены в коде и будут использоваться автоматически, даже если они не в PATH.

