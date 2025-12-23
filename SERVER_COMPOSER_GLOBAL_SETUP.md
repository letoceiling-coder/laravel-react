# Установка Composer глобально для всех пользователей

## Проблема
PHP работает от пользователя `root`, а composer установлен в `/home/d/dsc23ytp/bin/composer`, поэтому доступ ограничен.

## Решение 1: Создать симлинк в /usr/local/bin (если есть права)

```bash
# Создаем симлинк
sudo ln -s /home/d/dsc23ytp/bin/composer /usr/local/bin/composer

# Проверяем
which composer
composer --version
```

## Решение 2: Установить composer глобально (рекомендуется)

```bash
# Переходим в системную директорию
cd /usr/local/bin

# Скачиваем и устанавливаем composer
sudo php8.2 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php8.2 composer-setup.php
sudo mv composer.phar composer
sudo chmod +x composer
sudo rm composer-setup.php

# Проверяем
composer --version
```

## Решение 3: Использовать su в коде (уже реализовано)

Код уже обновлен для использования `su - dsc23ytp -c` при выполнении composer команд.

## Проверка

После установки проверь:

```bash
# От root
composer --version

# От пользователя dsc23ytp
su - dsc23ytp -c "composer --version"
```

## Если нет прав sudo

Если нет прав sudo, используй вариант с `su` в коде (уже реализовано) или попроси администратора выполнить установку.

