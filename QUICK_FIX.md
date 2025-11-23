# ⚡ Быстрое исправление ошибки "dubious ownership"

## Выполните на сервере:

```bash
# 1. Исправить проблему с правами доступа
git config --global --add safe.directory /home/d/dsc23ytp/parser-auto.site-access.ru/public_html

# 2. Переименовать ветку master в main
git branch -m master main

# 3. Теперь можно добавить файлы
git add .

# 4. Создать commit
git commit -m "Initial commit from server"

# 5. Добавить remote (замените YOUR_TOKEN на ваш GitHub токен)
git remote add origin https://YOUR_TOKEN@github.com/letoceiling-coder/bots.git

# 6. Отправить на GitHub
git push -u origin main
```

## 🔐 Получение GitHub токена

1. Перейдите: https://github.com/settings/tokens
2. "Generate new token" → "Generate new token (classic)"
3. Выберите права: `repo`
4. Скопируйте токен

## ✅ После этого команда deploy будет работать:

```bash
php artisan deploy --force
```


