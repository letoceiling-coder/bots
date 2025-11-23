# Инструкция по проверке webhook для бота

## Проблема: Бот не реагирует на команду /start

### Возможные причины:

1. **Webhook не установлен** - Telegram не знает, куда отправлять обновления
2. **Неправильный URL webhook** - Webhook указывает на неверный адрес
3. **Бот неактивен** - В базе данных бот помечен как неактивный
4. **Проблемы с маршрутизацией** - Роут не доступен из интернета

### Проверка:

1. **Проверьте статус бота в базе данных:**
   ```sql
   SELECT id, name, is_active, token FROM telegram_bots WHERE id = 1;
   ```
   Убедитесь, что `is_active = 1`

2. **Проверьте URL webhook:**
   - Ваш URL должен быть: `https://ваш-домен.ru/api/telegram/webhook/1`
   - URL должен быть доступен из интернета (HTTPS обязателен)
   - Можно использовать ngrok для локальной разработки

3. **Проверьте логи:**
   - Файл: `storage/logs/laravel.log`
   - Ищите записи с `Telegram webhook received`

### Установка webhook:

Вы можете установить webhook через Telegram Bot API:

```bash
curl -X POST "https://api.telegram.org/bot<ВАШ_ТОКЕН>/setWebhook?url=https://ваш-домен.ru/api/telegram/webhook/1"
```

Или использовать интерфейс админ-панели, если там есть такая функция.

### Проверка webhook через API:

```bash
curl "https://api.telegram.org/bot<ВАШ_ТОКЕН>/getWebhookInfo"
```

### Логи для отладки:

После моих изменений в коде, при получении команды /start в логах должны появиться записи:
- `Telegram webhook received` - webhook получен
- `Handling bot map update` - начинается обработка
- `Handling message` - обрабатывается сообщение
- `Handling bot command` - обрабатывается команда
- `Command block found` - блок команды найден
- `Executing block` - выполняется блок

Если этих записей нет, значит webhook не доходит до сервера.

