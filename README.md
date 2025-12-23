# Laravel + React Project

Проект с разделением на Backend (Laravel) и Frontend (React).

## Архитектура

- **Backend (Laravel)**: `/admin/**` - админ-панель, `/api/**` - API
- **Frontend (React)**: все остальные маршруты - публичная часть сайта

## Установка

### Backend

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Frontend

```bash
cd frontend
npm install
npm run build
```

Сборка должна быть в `frontend/dist/`

## Разработка

### Backend
```bash
php artisan serve
```

### Frontend (разработка)
```bash
cd frontend
npm run dev
```

## Роутинг

- `/admin/**` → Laravel админ-панель
- `/api/**` → Laravel API (JSON)
- `/**` → React frontend (или заглушка, если frontend не собран)

## Структура проекта

```
/
├── app/              # Laravel приложение
├── routes/           # Роуты Laravel
├── resources/        # Views, assets Laravel
├── frontend/         # React приложение
│   └── dist/         # Собранное React приложение
└── public/           # Точка входа
```

## Заглушка

Если `frontend/dist/index.html` отсутствует, показывается красивая заглушка "Сайт в разработке".

## Деплой

### Настройка локальной машины

Перед использованием команды `php artisan deploy` локально необходимо:

1. Инициализировать git репозиторий (`git init`)
2. Создать React приложение в `frontend/`
3. Настроить `.env` с параметрами деплоя

**Быстрое исправление:** `QUICK_FIX_LOCAL.md`  
**Подробная инструкция:** `LOCAL_SETUP.md`

### Настройка сервера

Перед использованием команды `php artisan deploy` на сервере необходимо:

1. Установить Composer в `~/bin/composer`
2. Установить Node.js через nvm
3. Настроить PATH в `.bashrc`

**Быстрая инструкция:** `QUICK_START_SERVER.md`  
**Подробная инструкция:** `SERVER_SETUP.md`  
**Команды для вашего сервера:** `SERVER_COMMANDS.md`

### Использование

```bash
# Локально
php artisan deploy

# На сервере (dsc23ytp@dragon)
php8.2 artisan deploy
```

**Документация:** `DEPLOY.md`
