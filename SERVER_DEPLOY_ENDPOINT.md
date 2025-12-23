# Настройка Deploy Endpoint на сервере

## Проблема

При выполнении `php artisan deploy` получаете ошибку 404:
```
HTTP 404: The requested URL was not found on this server.
```

Это означает, что на сервере нет endpoint `/api/deploy`.

---

## Решение

### 1. Убедитесь, что код с контроллером загружен на сервере

Контроллер `app/Http/Controllers/Api/DeployController.php` и роут в `routes/api.php` должны быть на сервере.

**Если на сервере нет git репозитория**, см. инструкцию: `SERVER_GIT_SETUP.md`

Если git уже настроен:

```bash
# На сервере
cd ~/laravel/public_html

# Pull из репозитория
git pull origin main
```

### 2. Проверьте настройки .env на сервере

Убедитесь, что в `.env` на сервере указан `DEPLOY_TOKEN`:

```bash
nano .env
```

Добавьте/проверьте:

```env
DEPLOY_TOKEN=your-secret-token-here
```

**ВАЖНО:** Токен должен совпадать с тем, что указан в `.env` на локальной машине!

### 3. Очистите кеш роутов

```bash
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
```

### 4. Проверьте доступность endpoint

```bash
# Проверка через curl
curl -X POST https://project.siteaccess.ru/api/deploy \
  -H "X-Deploy-Token: your-secret-token" \
  -H "Content-Type: application/json" \
  -d '{"commit_hash":"test","branch":"main"}'
```

Или проверьте через браузер/Postman:
- URL: `https://project.siteaccess.ru/api/deploy`
- Method: POST
- Headers: `X-Deploy-Token: your-secret-token`
- Body: JSON с данными

### 5. Проверьте логи

Если endpoint не работает, проверьте логи:

```bash
# Логи Laravel
tail -f storage/logs/laravel.log

# Логи веб-сервера (если доступны)
tail -f /var/log/apache2/error.log
# или
tail -f /var/log/nginx/error.log
```

---

## Проверка работы

После настройки выполните локально:

```bash
php artisan deploy --insecure
```

Должно вернуться:
- ✅ HTTP 200
- ✅ Сообщение об успешном деплое
- ✅ Информация о выполненных операциях

---

## Структура ответа endpoint

При успешном деплое endpoint вернет:

```json
{
  "success": true,
  "message": "Деплой выполнен успешно",
  "data": {
    "php_path": "/usr/local/php/cgi/8.2/bin/php",
    "php_version": "8.2.25",
    "git_pull": "success",
    "composer_install": "success",
    "migrations": {
      "status": "success",
      "message": "Миграции выполнены успешно"
    },
    "seeders": {
      "status": "skipped",
      "message": "Seeders пропущены"
    },
    "cache_clear": "success",
    "duration_seconds": 12.34,
    "deployed_at": "2025-12-23 12:33:39"
  }
}
```

---

## Устранение проблем

### Ошибка 404

1. Проверьте, что файлы контроллера и роутов на сервере
2. Очистите кеш роутов: `php8.2 artisan route:clear`
3. Проверьте, что роуты зарегистрированы: `php8.2 artisan route:list | grep deploy`

### Ошибка 401 (Unauthorized)

1. Проверьте `DEPLOY_TOKEN` в `.env` на сервере
2. Убедитесь, что токен совпадает с локальным `.env`
3. Проверьте заголовок `X-Deploy-Token` в запросе

### Ошибка 500 (Internal Server Error)

1. Проверьте логи: `tail -f storage/logs/laravel.log`
2. Проверьте права доступа к файлам
3. Убедитесь, что Composer и Git доступны

---

## Безопасность

⚠️ **ВАЖНО:**

1. **Никогда не коммитьте `DEPLOY_TOKEN` в git** - он должен быть только в `.env`
2. **Используйте сложный токен** (минимум 32 символа)
3. **Ограничьте доступ к endpoint** через файрвол/nginx если возможно
4. **Мониторьте логи** на подозрительные запросы

---

## Готово!

После выполнения всех шагов endpoint `/api/deploy` будет работать и команда `php artisan deploy` сможет автоматически обновлять код на сервере.

