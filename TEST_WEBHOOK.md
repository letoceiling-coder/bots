# Инструкция по установке и тестированию webhook

## ✅ Текущий статус

- **Бот активен**: ✅ `lawyers_decision_bot`
- **Карта загружена**: ✅ 30 блоков
- **Endpoint работает**: ✅ Возвращает JSON при GET запросе
- **Роут зарегистрирован**: ✅ `GET|POST|HEAD`

## 📋 Следующие шаги

### 1. Установить webhook в Telegram

На сервере выполните (замените `<BOT_TOKEN>` на реальный токен):

```bash
# Получите токен из базы данных
php8.2 artisan tinker --execute="echo \App\Models\Bot::find(1)?->token ?? 'not found';"

# Затем установите webhook (замените TOKEN на полученный токен)
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://parser-auto.siteaccess.ru/api/telegram/webhook/1"
```

### 2. Проверить статус webhook

```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

### 3. Отправить команду /start боту в Telegram

После установки webhook:
1. Откройте бота в Telegram
2. Отправьте `/start`
3. Проверьте логи:

```bash
tail -f storage/logs/laravel.log
```

### 4. Мониторинг логов в реальном времени

Откройте второй терминал и выполните:

```bash
tail -f storage/logs/laravel.log | grep -E "webhook|Telegram|command|start|bot_id.*1"
```

## 🔍 Диагностика

Если бот не отвечает после установки webhook:

1. **Проверьте, что webhook установлен**:
   ```bash
   curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
   ```
   
2. **Проверьте логи на ошибки**:
   ```bash
   tail -n 100 storage/logs/laravel.log | grep -i error
   ```

3. **Проверьте, что блок /start существует**:
   ```bash
   php8.2 artisan tinker --execute="\$bot = \App\Models\Bot::find(1); \$start = collect(\$bot->blocks ?? [])->firstWhere('command', '/start'); echo \$start ? 'Start block found: ' . \$start['id'] : 'Start block NOT found';"
   ```

4. **Проверьте доступность endpoint**:
   ```bash
   curl -X POST https://parser-auto.siteaccess.ru/api/telegram/webhook/1 \
     -H "Content-Type: application/json" \
     -d '{"update_id": 1, "message": {"message_id": 1, "from": {"id": 123, "first_name": "Test"}, "chat": {"id": 123}, "text": "/start"}}'
   ```

## ⚠️ Важно

- Webhook URL должен быть доступен из интернета (HTTPS обязателен)
- Telegram будет отправлять POST запросы на ваш endpoint
- Все запросы будут логироваться в `storage/logs/laravel.log`

