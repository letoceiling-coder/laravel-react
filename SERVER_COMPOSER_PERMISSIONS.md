# Исправление прав доступа к Composer

## Проблема
Права на файл `/home/d/dsc23ytp/bin/composer` - `-rwx--x--x+`, что означает:
- Владелец (dsc23ytp): может читать, писать, выполнять
- Группа и другие: могут только выполнять, но НЕ читать

PHP работает от пользователя `root`, который не может прочитать файл, поэтому возникает ошибка "Could not open input file".

## Решение

Выполни на сервере:

```bash
# Дать права на чтение всем
chmod 755 /home/d/dsc23ytp/bin/composer

# Или более безопасный вариант - только группе и другим на чтение
chmod 754 /home/d/dsc23ytp/bin/composer

# Проверь права
ls -la /home/d/dsc23ytp/bin/composer

# Должно быть: -rwxr-xr-x или -rwxr-xr--
```

После изменения прав попробуй деплой снова.

## Альтернативное решение

Если не хочешь менять права, можно скопировать composer в место, доступное root:

```bash
# Создать директорию (если нет)
mkdir -p ~/laravel/bin

# Скопировать composer
cp /home/d/dsc23ytp/bin/composer ~/laravel/bin/composer

# Дать права на выполнение
chmod +x ~/laravel/bin/composer

# Обновить .env
# COMPOSER_PATH=/home/d/dsc23ytp/laravel/bin/composer
```

Но проще всего просто дать права на чтение через `chmod 755`.

