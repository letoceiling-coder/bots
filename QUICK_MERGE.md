# ⚡ Быстрое решение ошибки "Updates were rejected"

## Выполните на сервере:

```bash
# 1. Объединить истории (получить код с GitHub и объединить с серверным)
git pull origin main --allow-unrelated-histories

# 2. Если все прошло без конфликтов, отправьте:
git push -u origin main

# 3. Если были конфликты, разрешите их:
# git add .
# git commit -m "Merge server and GitHub histories"
# git push -u origin main
```

## ✅ Готово!

После этого команда `php artisan deploy --force` будет работать.


