# Установка webhook для Telegram бота

## Токен бота
```
7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM
```

## Команды для выполнения на сервере

### Вариант 1: Использовать скрипт (если установлен jq)

```bash
cd ~/parser-auto.site-access.ru/public_html
chmod +x setup_webhook.sh
./setup_webhook.sh
```

### Вариант 2: Выполнить команды вручную

```bash
TOKEN="7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM"
WEBHOOK_URL="https://parser-auto.siteaccess.ru/api/telegram/webhook/1"

# 1. Проверка информации о боте (должен вернуть данные бота)
curl "https://api.telegram.org/bot${TOKEN}/getMe"

# 2. Установка webhook
curl -X POST "https://api.telegram.org/bot${TOKEN}/setWebhook?url=${WEBHOOK_URL}"

# 3. Проверка статуса webhook
curl "https://api.telegram.org/bot${TOKEN}/getWebhookInfo"
```

### Вариант 3: Одна строка (для копирования)

```bash
TOKEN="7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM" && curl -X POST "https://api.telegram.org/bot${TOKEN}/setWebhook?url=https://parser-auto.siteaccess.ru/api/telegram/webhook/1"
```

## Ожидаемый результат

### При успешной установке webhook:
```json
{
    "ok": true,
    "result": true,
    "description": "Webhook was set"
}
```

### При проверке статуса:
```json
{
    "ok": true,
    "result": {
        "url": "https://parser-auto.siteaccess.ru/api/telegram/webhook/1",
        "has_custom_certificate": false,
        "pending_update_count": 0
    }
}
```

## Если ошибка 404

Ошибка 404 "Not Found" означает:
- Токен неверный или бот удален
- Токен содержит невидимые символы
- Проблема с сетью Telegram

### Проверка токена:
```bash
curl "https://api.telegram.org/bot7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM/getMe"
```

Если токен верный, должен вернуться JSON с информацией о боте.

## После установки webhook

1. Откройте бота в Telegram
2. Отправьте команду `/start`
3. Проверьте логи:

```bash
tail -f storage/logs/laravel.log | grep -i "telegram\|webhook\|command\|start"
```

## Удаление webhook (если нужно)

```bash
TOKEN="7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM"
curl -X POST "https://api.telegram.org/bot${TOKEN}/deleteWebhook"
```

