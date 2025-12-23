# Настройка локальной машины для Deploy

## Проблемы и решения

### ❌ Проблема 1: "not a git repository"

**Ошибка:**
```
fatal: not a git repository (or any of the parent directories): .git
```

**Решение:** Инициализируйте git репозиторий:

```bash
# Инициализация git
git init

# Добавление remote (замените на ваш репозиторий)
git remote add origin https://github.com/username/repo.git

# Или если remote уже существует, проверьте:
git remote -v
```

### ❌ Проблема 2: "package.json не найден в frontend/"

**Ошибка:**
```
⚠️  package.json не найден в frontend/
💡 Пропускаем сборку. Создайте React приложение в frontend/
```

**Решение:** Создайте React приложение в папке `frontend/`:

```bash
cd frontend

# Создание React приложения через Vite
npm create vite@latest . -- --template react

# Или если папка пустая, создайте в подпапке и переместите:
npm create vite@latest temp-react -- --template react
mv temp-react/* .
mv temp-react/.* . 2>/dev/null || true
rmdir temp-react

# Установка зависимостей
npm install

# Проверка сборки
npm run build

cd ..
```

---

## Полная настройка локальной машины

### 1. Инициализация Git

```bash
# Проверка текущего статуса
git status

# Если ошибка "not a git repository", выполните:
git init

# Добавьте файлы в git
git add .

# Создайте первый коммит
git commit -m "Initial commit"

# Добавьте remote (если еще не добавлен)
git remote add origin https://github.com/username/repo.git

# Или проверьте существующий remote
git remote -v
```

### 2. Создание React приложения

```bash
# Перейдите в папку frontend
cd frontend

# Создайте React приложение (Vite)
npm create vite@latest . -- --template react

# Установите зависимости
npm install

# Проверьте работу
npm run dev

# Проверьте сборку
npm run build

# Вернитесь в корень проекта
cd ..
```

### 3. Настройка .env

Убедитесь, что в `.env` указаны настройки деплоя:

```env
DEPLOY_SERVER_URL=https://your-server.com
DEPLOY_TOKEN=your-secret-token
GIT_REPOSITORY_URL=https://github.com/username/repo.git
```

### 4. Проверка Deploy

```bash
# Dry-run (проверка без выполнения)
php artisan deploy --dry-run

# Реальный деплой
php artisan deploy
```

---

## Структура проекта после настройки

```
laravel-react/
├── .git/                    # Git репозиторий
├── app/
├── frontend/
│   ├── package.json        # ✅ Должен существовать
│   ├── vite.config.js
│   ├── src/
│   └── dist/               # После сборки
├── routes/
├── .env                    # С настройками деплоя
└── ...
```

---

## Проверочный список

- [ ] Git репозиторий инициализирован (`git init`)
- [ ] Git remote добавлен (`git remote add origin ...`)
- [ ] React приложение создано в `frontend/`
- [ ] `frontend/package.json` существует
- [ ] Зависимости установлены (`npm install` в frontend)
- [ ] Сборка работает (`npm run build` в frontend)
- [ ] `.env` настроен (DEPLOY_SERVER_URL, DEPLOY_TOKEN)
- [ ] `php artisan deploy --dry-run` работает без ошибок

---

## Полезные команды

```bash
# Git
git status
git remote -v
git add .
git commit -m "Описание"
git push origin main

# Frontend
cd frontend
npm install
npm run dev
npm run build

# Deploy
php artisan deploy --dry-run
php artisan deploy
php artisan deploy --message="Описание изменений"
```

---

## После настройки

После выполнения всех шагов команда `php artisan deploy` будет работать корректно как локально, так и на сервере.

