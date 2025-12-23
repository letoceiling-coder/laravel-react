# Исправление проблемы с Composer на сервере

## Проблема

При деплое получаете ошибку:
```
Composer: error: sh: 1: composer: not found
```

## Решение

### 1. Добавьте COMPOSER_PATH в .env на сервере

```bash
cd ~/laravel/public_html
nano .env
```

Добавьте строку:

```env
COMPOSER_PATH=/home/d/dsc23ytp/bin/composer
```

### 2. Очистите кеш конфигурации

```bash
php8.2 artisan config:clear
```

### 3. Проверьте работу

Выполните локально:

```bash
php artisan deploy --insecure
```

Теперь должно показать:
- ✅ Composer: success

---

## Альтернатива: Проверка пути

Если не уверены в пути, проверьте:

```bash
which composer
# или
ls -la ~/bin/composer
```

Затем укажите точный путь в `.env`:
```env
COMPOSER_PATH=/home/d/dsc23ytp/bin/composer
```

---

## После исправления

DeployController автоматически использует путь из `COMPOSER_PATH` и composer install будет работать корректно.

