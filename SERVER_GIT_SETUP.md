# Настройка Git на сервере

## Проблема

На сервере нет git репозитория:
```
fatal: не найден git репозиторий
```

## Решение

### Вариант 1: Инициализация нового репозитория (если код уже есть на сервере)

Если код Laravel уже есть на сервере, но нет git:

```bash
cd ~/laravel/public_html

# Инициализация git
git init

# Добавление remote репозитория
git remote add origin https://github.com/username/repo.git

# Проверка remote
git remote -v

# Добавление всех файлов
git add .

# Создание первого коммита
git commit -m "Initial commit from server"

# Установка upstream и отправка
git branch -M main
git push -u origin main
```

### Вариант 2: Клонирование репозитория (если кода еще нет)

Если на сервере нет кода проекта:

```bash
# Перейдите в родительскую директорию
cd ~/laravel

# Удалите или переименуйте старую папку (если есть)
mv public_html public_html_backup 2>/dev/null || true

# Клонируйте репозиторий
git clone https://github.com/username/repo.git public_html

# Перейдите в проект
cd public_html

# Настройте .env
cp .env.example .env
nano .env

# Установите зависимости
composer install --no-dev --optimize-autoloader
cd frontend && npm install && cd ..

# Выполните миграции
php8.2 artisan migrate
```

### Вариант 3: Подключение к существующему репозиторию (если код есть, но git не настроен)

Если код есть, но нужно подключить к существующему репозиторию:

```bash
cd ~/laravel/public_html

# Инициализация git
git init

# Добавление remote
git remote add origin https://github.com/letoceiling-coder/laravel-react.git

# Получение данных из удаленного репозитория
git fetch origin

# Проверка веток
git branch -r

# Подключение к ветке main
git checkout -b main origin/main

# Или если нужно перезаписать локальные файлы
git reset --hard origin/main
```

---

## После настройки Git

### 1. Настройте .env на сервере

```bash
nano .env
```

Убедитесь, что указаны:

```env
DEPLOY_TOKEN=your-secret-token-here
DEPLOY_SERVER_URL=https://project.siteaccess.ru
GIT_REPOSITORY_URL=https://github.com/username/repo.git
```

### 2. Очистите кеш

```bash
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
```

### 3. Проверьте endpoint

```bash
php8.2 artisan route:list | grep deploy
```

Должен показать: `POST api/deploy`

### 4. Проверьте работу деплоя

Выполните локально:

```bash
php artisan deploy --insecure
```

---

## Важные замечания

1. **Если используете HTTPS для git**, может потребоваться настройка credentials
2. **Если используете SSH для git**, настройте SSH ключи на сервере
3. **Убедитесь, что `.env` не в git** - он должен быть в `.gitignore`
4. **После первого клонирования** настройте `.env` и выполните миграции

---

## Проверка работы Git

```bash
# Проверка статуса
git status

# Проверка remote
git remote -v

# Проверка веток
git branch -a

# Тестовый pull
git pull origin main
```

---

## Если возникли проблемы

### Ошибка доступа к репозиторию

```bash
# Для HTTPS - настройте credentials
git config --global credential.helper store

# Для SSH - настройте ключи
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"
cat ~/.ssh/id_rsa.pub
# Добавьте публичный ключ в GitHub/GitLab
```

### Конфликты при pull

```bash
# Если есть локальные изменения, которые нужно сохранить
git stash
git pull origin main
git stash pop

# Если нужно перезаписать локальные изменения
git reset --hard origin/main
```

---

## Готово!

После настройки Git на сервере команда `php artisan deploy` сможет автоматически обновлять код через `git pull`.

