# Настройка базы данных MySQL

## Текущая конфигурация

База данных настроена в `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_react
DB_USERNAME=root
DB_PASSWORD=
```

## Создание базы данных

База данных уже создана автоматически через скрипт `create_database.php`.

Если нужно создать вручную:

### Вариант 1: Через PHP скрипт (уже выполнено)

```bash
php create_database.php
```

### Вариант 2: Через phpMyAdmin

1. Откройте phpMyAdmin (обычно http://localhost/phpMyAdmin)
2. Перейдите на вкладку "SQL"
3. Выполните SQL:

```sql
CREATE DATABASE IF NOT EXISTS `laravel_react` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### Вариант 3: Через командную строку MySQL

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS laravel_react CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## Выполнение миграций

После создания базы данных выполните миграции:

```bash
php artisan migrate
```

## Проверка подключения

Проверить статус миграций:

```bash
php artisan migrate:status
```

## Настройка переменных деплоя

В файле `.env` добавьте следующие переменные:

```env
# Deploy Configuration
DEPLOY_SERVER_URL=https://your-server.com
DEPLOY_TOKEN=your-secret-token
GIT_REPOSITORY_URL=https://github.com/username/repo.git
```

**Примечание:** `GIT_REPOSITORY_URL` опционален - если не указан, команда деплоя автоматически определит его из текущего git remote.

