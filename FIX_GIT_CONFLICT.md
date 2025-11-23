# Исправление конфликта при git pull на сервере

## Проблема
Git не может выполнить pull, потому что есть локальные изменения, которые конфликтуют с удаленными изменениями.

## Решение

### Вариант 1: Сохранить локальные изменения и обновить (рекомендуется)

```bash
cd ~/parser-auto.site-access.ru/public_html

# Сохранить локальные изменения во временное хранилище
git stash save "Локальные изменения перед обновлением"

# Получить изменения из репозитория
git pull origin main

# Просмотреть сохраненные изменения (опционально)
git stash list

# Если нужно восстановить сохраненные изменения (опционально)
# git stash pop
```

### Вариант 2: Откатить локальные изменения (если они не нужны)

```bash
cd ~/parser-auto.site-access.ru/public_html

# Посмотреть, какие изменения будут потеряны
git status
git diff

# Если изменения не нужны, откатить их
git checkout -- app/Http/Controllers/Api/TelegramWebhookController.php
git checkout -- app/Services/BotMapHandler.php
git checkout -- app/Services/ExtendedTelegraph.php
git checkout -- routes/api.php

# Удалить лишние файлы (если они не нужны)
rm -f ur.rar

# Теперь можно выполнить pull
git pull origin main
```

### Вариант 3: Закоммитить локальные изменения (если они важны)

```bash
cd ~/parser-auto.site-access.ru/public_html

# Посмотреть изменения
git status
git diff

# Добавить изменения
git add app/Http/Controllers/Api/TelegramWebhookController.php
git add app/Services/BotMapHandler.php
git add app/Services/ExtendedTelegraph.php
git add routes/api.php

# Закоммитить (если изменения важны)
git commit -m "Локальные изменения на сервере"

# Теперь можно выполнить pull (может потребоваться разрешение конфликтов)
git pull origin main
```

## Рекомендуемая последовательность действий

Для данного случая рекомендую **Вариант 1** (stash), так как:
1. Сохраняет все локальные изменения
2. Позволяет обновиться из репозитория
3. Дает возможность потом решить, нужны ли локальные изменения

Выполните:
```bash
cd ~/parser-auto.site-access.ru/public_html
git stash save "Локальные изменения перед обновлением"
git pull origin main
php8.2 artisan optimize:clear
```

После этого локальные изменения будут сохранены в stash и при необходимости их можно восстановить командой `git stash pop`.

