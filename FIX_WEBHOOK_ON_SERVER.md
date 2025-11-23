# Исправление webhook endpoint на сервере

## Проблема
- `route:list` показывает только `POST` вместо `GET|POST`
- Curl возвращает HTML вместо JSON
- URL `/api/telegram/webhook/1` показывает Vue приложение

## Причина
1. Файл `routes/api.php` на сервере содержит старую версию (только POST)
2. Кэш роутов не обновился
3. Изменения не были отправлены в Git репозиторий

## Решение на сервере

### Вариант 1: Прямое исправление файла (быстро)

На сервере выполните:

```bash
cd ~/parser-auto.site-access.ru/public_html

# Откройте файл для редактирования
nano routes/api.php
```

Найдите строку (около строки 30):
```php
Route::post('/telegram/webhook/{bot_id}', [TelegramWebhookController::class, 'handle'])
```

И замените на:
```php
Route::match(['GET', 'POST'], '/telegram/webhook/{bot_id}', [TelegramWebhookController::class, 'handle'])
```

Сохраните файл (Ctrl+O, Enter, Ctrl+X)

### Вариант 2: Через sed (автоматически)

```bash
cd ~/parser-auto.site-access.ru/public_html

# Создайте резервную копию
cp routes/api.php routes/api.php.backup

# Замените POST на match(['GET', 'POST'])
sed -i "s/Route::post('\/telegram\/webhook/{bot_id}',/Route::match(['GET', 'POST'], '\/telegram\/webhook\/{bot_id}',/" routes/api.php
```

### После исправления файла

```bash
# Очистите кэш
php8.2 artisan route:clear
php8.2 artisan cache:clear
php8.2 artisan config:clear

# Проверьте, что роут обновился
php8.2 artisan route:list | grep webhook

# Должно показать:
# GET|POST  api/telegram/webhook/{bot_id}  telegram.webhook › Api\TelegramWebhookController@handle

# Проверьте через curl
curl https://parser-auto.siteaccess.ru/api/telegram/webhook/1

# Должен вернуться JSON, а не HTML
```

### Проверка результата

После исправления команда должна вернуть JSON:

```json
{
    "status": "ok",
    "message": "Webhook endpoint is active",
    "bot_id": "1",
    "bot_name": "lawyers_decision_bot",
    "bot_active": true,
    "method": "GET",
    "note": "This endpoint accepts POST requests from Telegram",
    "webhook_url": "https://parser-auto.siteaccess.ru/api/telegram/webhook/1",
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

## Альтернативное решение: Проверить и применить изменения из Git

Если изменения были закоммичены:

```bash
cd ~/parser-auto.site-access.ru/public_html

# Проверьте статус файла
git status routes/api.php

# Если файл изменен локально, посмотрите изменения
git diff routes/api.php

# Если нужно применить изменения из репозитория
git checkout routes/api.php
git pull origin main

# Или принудительно обновить
git fetch origin main
git reset --hard origin/main

# Затем очистить кэш
php8.2 artisan route:clear
php8.2 artisan cache:clear
```

