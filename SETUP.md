# Инструкция по настройке проекта

## Настройка статических файлов React

После сборки React приложения (`npm run build` в папке `frontend`), статические файлы (CSS, JS, изображения) должны быть доступны через веб-сервер.

### Вариант 1: Симлинк (рекомендуется для продакшена)

Создайте симлинк для статических файлов:

**Windows (PowerShell от имени администратора):**
```powershell
New-Item -ItemType SymbolicLink -Path "public\assets" -Target "..\frontend\dist\assets"
```

**Linux/Mac:**
```bash
ln -s ../frontend/dist/assets public/assets
```

### Вариант 2: Настройка веб-сервера

Настройте ваш веб-сервер (nginx/apache) для прямой отдачи файлов из `frontend/dist/assets`.

**Пример для nginx:**
```nginx
location /assets {
    alias /path/to/project/frontend/dist/assets;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Вариант 3: Копирование при деплое

Скопируйте содержимое `frontend/dist` в `public` при деплое:
```bash
cp -r frontend/dist/* public/
```

## Разработка

При разработке используйте Vite dev server:
```bash
cd frontend
npm run dev
```

Vite будет работать на порту 5173, а Laravel на 8000. Настройте прокси в `vite.config.js` для API запросов.

