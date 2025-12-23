# Быстрое исправление проблем на локальной машине

## ✅ Что уже сделано

- Git репозиторий инициализирован

## 🔧 Что нужно сделать

### 1. Создать React приложение в frontend/

```bash
cd frontend

# Создание React приложения через Vite
npm create vite@latest . -- --template react

# Установка зависимостей
npm install

# Проверка сборки
npm run build

cd ..
```

### 2. Добавить remote репозиторий (если нужно)

```bash
# Проверьте, есть ли remote
git remote -v

# Если нет, добавьте:
git remote add origin https://github.com/username/repo.git
```

### 3. Настроить .env

Убедитесь, что в `.env` есть:

```env
DEPLOY_SERVER_URL=https://your-server.com
DEPLOY_TOKEN=your-secret-token
GIT_REPOSITORY_URL=https://github.com/username/repo.git
```

### 4. Проверить Deploy

```bash
php artisan deploy --dry-run
```

---

## 📝 После этого

Команда `php artisan deploy` будет работать корректно!

