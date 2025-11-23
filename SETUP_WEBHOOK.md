# Настройка webhook для Telegram бота

## Текущий статус

✅ Webhook endpoint работает и доступен:
- URL: `https://parser-auto.siteaccess.ru/api/telegram/webhook/1`
- GET запрос возвращает JSON с информацией о боте
- Роут зарегистрирован: `GET|POST|HEAD`

## Шаг 1: Получить токен бота

Убедитесь, что у вас есть токен бота из BotFather. Бот должен быть в базе данных с `id = 1`.

## Шаг 2: Установить webhook в Telegram

Выполните команду (замените `<BOT_TOKEN>` на реальный токен бота):

```bash
curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/setWebhook?url=https://parser-auto.siteaccess.ru/api/telegram/webhook/1"
```

### Пример:
```bash
curl -X POST "https://api.telegram.org/bot1234567890:ABCdefGHIjklMNOpqrsTUVwxyz/setWebhook?url=https://parser-auto.siteaccess.ru/api/telegram/webhook/1"
```

### Ожидаемый ответ:
```json
{
    "ok": true,
    "result": true,
    "description": "Webhook was set"
}
```

## Шаг 3: Проверить статус webhook

```bash
curl "https://api.telegram.org/bot<BOT_TOKEN>/getWebhookInfo"
```

### Ожидаемый ответ:
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

## Шаг 4: Проверить, что бот активен в базе данных

На сервере выполните (если есть доступ к MySQL):

```bash
php8.2 artisan tinker
```

Затем в tinker:
```php
$bot = \App\Models\Bot::find(1);
echo "Bot: " . $bot->name . "\n";
echo "Active: " . ($bot->is_active ? 'yes' : 'no') . "\n";
echo "Has blocks: " . (count($bot->blocks ?? []) > 0 ? 'yes' : 'no') . "\n";
```

Или через SQL:
```sql
SELECT id, name, is_active, 
       JSON_LENGTH(blocks) as blocks_count 
FROM telegram_bots 
WHERE id = 1;
```

Убедитесь, что:
- `is_active = 1`
- `blocks_count > 0` (карта бота загружена)

## Шаг 5: Тестирование

1. Откройте бота в Telegram
2. Отправьте команду `/start`
3. Проверьте логи на сервере:

```bash
tail -f storage/logs/laravel.log | grep -i "telegram\|webhook\|start"
```

Должны появиться записи:
- `Telegram webhook received`
- `Handling bot map update`
- `Handling message`
- `Handling bot command`
- `Command block found`
- `Executing block`

## Шаг 6: Если бот не отвечает

1. **Проверьте логи** на наличие ошибок
2. **Убедитесь, что webhook установлен** правильно
3. **Проверьте, что бот активен** (`is_active = 1`)
4. **Проверьте, что карта бота загружена** (блоки существуют)
5. **Проверьте, что в карте есть блок с `command = '/start'`**

## Полезные команды

### Удалить webhook (если нужно)
```bash
curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/deleteWebhook"
```

### Отправить тестовое сообщение вручную (для проверки)
```bash
curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/sendMessage" \
  -H "Content-Type: application/json" \
  -d '{"chat_id": "YOUR_CHAT_ID", "text": "Test message"}'
```

### Проверить информацию о боте
```bash
curl "https://api.telegram.org/bot<BOT_TOKEN>/getMe"
```

## Структура блока /start

В базе данных должен быть блок с:
- `id = "1"`
- `command = "/start"`
- `method = "sendMessage"`
- `method_data.text` - текст приветствия
- `nextBlockId = "2"` - переход к главному меню

