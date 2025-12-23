# Исправление Git Remote на сервере

## Проблема

Используется неправильный URL репозитория:
```
https://github.com/username/repo.git
```

## Решение

### 1. Обновите remote на правильный URL

```bash
cd ~/laravel/public_html

# Удалите старый remote
git remote remove origin

# Добавьте правильный remote
git remote add origin https://github.com/letoceiling-coder/laravel-react.git

# Проверьте
git remote -v
```

### 2. Получите данные из репозитория

```bash
# Получение данных
git fetch origin

# Проверка веток
git branch -r
```

### 3. Подключитесь к ветке main

**Если ветка main уже существует (ваш случай):**

```bash
# Переключитесь на существующую ветку main
git checkout main

# Обновите из удаленного репозитория
git pull origin main

# Или если нужно перезаписать локальные изменения
git reset --hard origin/main
```

**Если ветки main нет:**

```bash
# Создайте ветку из удаленной
git checkout -b main origin/main
```

### 4. Отправка изменений (если нужно)

Если вы хотите отправить локальные изменения в репозиторий:

```bash
# Проверьте статус
git status

# Если есть изменения, добавьте их
git add .

# Создайте коммит
git commit -m "Описание изменений"

# Отправьте в репозиторий
git push origin main
```

**Примечание:** Для отправки в GitHub может потребоваться авторизация через Personal Access Token вместо пароля.

---

## Настройка авторизации GitHub

### Использование Personal Access Token

1. Создайте токен на GitHub: Settings → Developer settings → Personal access tokens → Tokens (classic)
2. При push используйте токен вместо пароля:

```bash
# При запросе пароля введите токен
git push origin main
# Username: letoceiling-coder
# Password: <ваш_токен>
```

### Или настройте через SSH (рекомендуется)

```bash
# Генерируйте SSH ключ (если еще нет)
ssh-keygen -t ed25519 -C "your_email@example.com"

# Покажите публичный ключ
cat ~/.ssh/id_ed25519.pub

# Добавьте ключ в GitHub: Settings → SSH and GPG keys → New SSH key

# Измените remote на SSH
git remote set-url origin git@github.com:letoceiling-coder/laravel-react.git

# Проверьте
git remote -v
```

---

## Проверка работы

```bash
# Проверка remote
git remote -v
# Должно показать: origin  https://github.com/letoceiling-coder/laravel-react.git

# Проверка статуса
git status

# Тестовый pull
git pull origin main
```

---

## После настройки

1. Убедитесь, что контроллер DeployController на сервере
2. Настройте `.env` с `DEPLOY_TOKEN`
3. Очистите кеш: `php8.2 artisan route:clear`
4. Проверьте endpoint: `php8.2 artisan route:list | grep deploy`

