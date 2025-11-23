# ⚡ Быстрое разрешение всех конфликтов

## Выполните на сервере:

```bash
# 1. Принять все изменения с GitHub (рекомендуется)
git checkout --theirs .

# 2. Добавить все файлы
git add .

# 3. Завершить merge
git commit -m "Merge server and GitHub histories"

# 4. Отправить на GitHub
git push -u origin main
```

## ✅ Готово!

После этого команда `php artisan deploy --force` будет работать.

