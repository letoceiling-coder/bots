# Исправление: кнопки не реагируют (callback_query не приходит)

## Проблема
Кнопки отправляются в Telegram, но при нажатии на них ничего не происходит. В логах нет записей о callback_query.

## Причина
Webhook не настроен для получения обновлений типа `callback_query`. Telegram по умолчанию может отправлять только `message`, если не указано иное.

## Решение

### Шаг 1: Проверить текущие настройки webhook

```bash
curl "https://api.telegram.org/bot7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM/getWebhookInfo"
```

### Шаг 2: Установить webhook с поддержкой callback_query

Выполните на сервере:

```bash
curl -X POST "https://api.telegram.org/bot7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM/setWebhook?url=https://parser-auto.siteaccess.ru/api/telegram/webhook/1&allowed_updates=[\"message\",\"callback_query\"]"
```

Или используйте PHP скрипт для проверки и настройки:

```bash
cd ~/parser-auto.site-access.ru/public_html
php check_webhook_callback.php
```

### Шаг 3: Проверить, что webhook настроен правильно

```bash
curl "https://api.telegram.org/bot7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM/getWebhookInfo" | jq
```

Должно быть:
- `"url": "https://parser-auto.siteaccess.ru/api/telegram/webhook/1"`
- `"allowed_updates": ["message", "callback_query"]` или пустой массив (тогда разрешены все типы)

### Шаг 4: Тестирование

1. Отправьте `/start` боту
2. Нажмите на любую кнопку
3. Проверьте логи:

```bash
tail -f storage/logs/laravel.log | grep -E "(callback|Callback|webhook)"
```

Должны появиться записи:
- `"has_callback_query":true` в логах webhook
- `Handling callback query` в логах BotMapHandler

## Альтернативный способ (разрешить все типы обновлений)

Если хотите разрешить все типы обновлений:

```bash
curl -X POST "https://api.telegram.org/bot7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM/setWebhook?url=https://parser-auto.siteaccess.ru/api/telegram/webhook/1"
```

(без параметра `allowed_updates` - тогда Telegram будет отправлять все типы обновлений)

## Проверка кнопок в базе данных

Если после настройки webhook кнопки все еще не работают, проверьте, что у кнопок есть callback_data:

```bash
cd ~/parser-auto.site-access.ru/public_html
php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$bot = App\Models\Bot::find(1); \$blocks = is_string(\$bot->blocks) ? json_decode(\$bot->blocks, true) : \$bot->blocks; \$menu = array_filter(\$blocks, fn(\$b) => (\$b['id'] ?? null) === '2'); \$menu = reset(\$menu); if (\$menu && isset(\$menu['methodData']['inline_keyboard'])) { foreach (\$menu['methodData']['inline_keyboard'] as \$row) { foreach (\$row as \$btn) { echo (\$btn['text'] ?? 'no text') . ' | callback_data: ' . (\$btn['callback_data'] ?? 'MISSING') . PHP_EOL; } } }"
```

Все кнопки должны иметь `callback_data`.

## После исправления

После настройки webhook кнопки должны работать. При нажатии на кнопку:

1. Telegram отправит callback_query на webhook
2. В логах появится запись `"has_callback_query":true`
3. BotMapHandler обработает callback_query
4. Будет вызван `answerCallbackQuery`
5. Будет выполнен соответствующий блок карты

