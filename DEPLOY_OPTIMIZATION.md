# Оптимизация команды Deploy для Shared-хостинга

## Что было сделано

### 1. Автоматическое определение путей к инструментам

Команда `php artisan deploy` теперь автоматически определяет пути к `npm` и `composer` в следующем порядке:

#### Для npm:
1. Опция `--npm-path` (приоритет)
2. Переменная `NPM_PATH` в `.env`
3. Команда `which npm` (если в PATH)
4. Поиск в `~/.nvm/versions/node/*/bin/npm` (для nvm)
5. Стандартный путь `npm`

#### Для composer:
1. Опция `--composer-path` (приоритет)
2. Переменная `COMPOSER_PATH` в `.env`
3. Команда `which composer` (если в PATH)
4. Поиск в `~/bin/composer` (пользовательский путь)
5. Стандартный путь `composer`

### 2. Документация для настройки сервера

Созданы файлы:
- `SETUP_SERVER.md` - подробная инструкция по настройке сервера
- `scripts/setup-server.sh` - автоматический скрипт настройки

### 3. Новые опции команды

```bash
# Указать путь к npm вручную
php artisan deploy --npm-path=/home/user/.nvm/versions/node/v20.10.0/bin/npm

# Указать путь к composer вручную
php artisan deploy --composer-path=/home/user/bin/composer
```

### 4. Переменные окружения

Добавлены в `.env.example`:
```env
# Пути к инструментам (опционально, определяется автоматически)
NPM_PATH=
COMPOSER_PATH=
```

## Использование на Shared-хостинге (Beget)

### Вариант 1: Автоматическая настройка (рекомендуется)

1. Подключитесь по SSH к серверу
2. Выполните скрипт настройки:
   ```bash
   cd ~
   curl -o setup-server.sh https://raw.githubusercontent.com/your-repo/scripts/setup-server.sh
   chmod +x setup-server.sh
   ./setup-server.sh
   ```
   Или скопируйте `scripts/setup-server.sh` на сервер и выполните.

3. Переподключитесь по SSH для применения изменений PATH

4. Команда `php artisan deploy` автоматически найдет инструменты

### Вариант 2: Ручная настройка

Следуйте инструкции в `SETUP_SERVER.md`:
1. Установите Composer в `~/bin/composer`
2. Установите Node.js через nvm
3. Добавьте пути в `.bashrc`

### Вариант 3: Указание путей вручную

Если автоматическое определение не работает, укажите пути в `.env`:

```env
NPM_PATH=/home/login/.nvm/versions/node/v20.10.0/bin/npm
COMPOSER_PATH=/home/login/bin/composer
```

Или используйте опции команды:

```bash
php artisan deploy --npm-path=/home/login/.nvm/versions/node/v20.10.0/bin/npm
```

## Преимущества оптимизации

✅ **Работает на shared-хостинге** без root-доступа  
✅ **Автоматическое определение** путей к инструментам  
✅ **Гибкая настройка** через переменные окружения или опции  
✅ **Поддержка nvm** для Node.js  
✅ **Поддержка пользовательских путей** для Composer  
✅ **Обратная совместимость** - работает и на обычных серверах  

## Проверка работы

```bash
# Dry-run для проверки
php artisan deploy --dry-run

# Проверка определения путей
php artisan deploy --dry-run --verbose
```

Команда покажет, какие пути к npm и composer были определены.

## Дополнительная информация

- Подробная инструкция: `SETUP_SERVER.md`
- Документация по деплою: `DEPLOY.md`
- Скрипт настройки: `scripts/setup-server.sh`

