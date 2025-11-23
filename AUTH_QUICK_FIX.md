# ⚡ Быстрое исправление ошибки аутентификации

## Проблема
Вы использовали `YOUR_TOKEN` вместо реального токена GitHub.

## Решение

### Шаг 1: Получите токен GitHub

1. Перейдите: https://github.com/settings/tokens
2. "Generate new token" → "Generate new token (classic)"
3. Выберите права: `repo`
4. Скопируйте токен (начинается с `ghp_`)

### Шаг 2: Используйте токен

```bash
# Удалить старый remote
git remote remove origin

# Добавить remote с РЕАЛЬНЫМ токеном (замените ghp_xxxxx на ваш токен)
git remote add origin https://ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx@github.com/letoceiling-coder/bots.git

# Отправить код
git push -u origin main
```

### Пример:
```bash
git remote remove origin
git remote add origin https://ghp_abc123def456ghi789jkl012mno345pqr678stu901vwx234yz@github.com/letoceiling-coder/bots.git
git push -u origin main
```

## ✅ Готово!

После этого команда `php artisan deploy --force` будет работать.

