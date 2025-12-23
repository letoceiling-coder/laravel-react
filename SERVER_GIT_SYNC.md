# Синхронизация Git на сервере

## Текущая ситуация

- Локальная ветка `main` уже существует
- Удаленная ветка `origin/main` получена
- Нужно синхронизировать локальную ветку с удаленной

## Решение

### Вариант 1: Обновить существующую ветку (рекомендуется)

```bash
cd ~/laravel/public_html

# Переключитесь на ветку main
git checkout main

# Обновите из удаленного репозитория
git pull origin main

# Или если есть конфликты и нужно перезаписать локальные изменения
git reset --hard origin/main
```

### Вариант 2: Удалить локальную ветку и создать заново

```bash
cd ~/laravel/public_html

# Переключитесь на другую ветку (если есть)
git checkout -b temp 2>/dev/null || git checkout main

# Удалите локальную ветку main
git branch -D main

# Создайте новую ветку из удаленной
git checkout -b main origin/main
```

---

## После синхронизации

### 1. Проверьте наличие DeployController

```bash
# Проверьте файл
ls -la app/Http/Controllers/Api/DeployController.php

# Если файла нет, значит код не синхронизирован
```

### 2. Если DeployController отсутствует

Выполните pull еще раз:

```bash
git pull origin main
```

### 3. Настройте .env

```bash
nano .env
```

Убедитесь, что есть:

```env
DEPLOY_TOKEN=your-secret-token-here
DEPLOY_SERVER_URL=https://project.siteaccess.ru
```

### 4. Очистите кеш

```bash
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
```

### 5. Проверьте endpoint

```bash
php8.2 artisan route:list | grep deploy
```

Должен показать: `POST api/deploy`

---

## Проверка статуса

```bash
# Статус git
git status

# Проверка веток
git branch -a

# Последние коммиты
git log --oneline -5

# Проверка синхронизации
git log HEAD..origin/main
```

Если `git log HEAD..origin/main` ничего не показывает, значит ветки синхронизированы.

---

## Готово!

После синхронизации команда `php artisan deploy` с локальной машины сможет обновлять код на сервере через endpoint `/api/deploy`.

