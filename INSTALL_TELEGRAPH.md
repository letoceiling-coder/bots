# Установка и настройка Telegraph

## Шаги установки

1. **Установите пакет через Composer:**
```bash
composer require defstudio/telegraph
```

2. **Опубликуйте миграции:**
```bash
php artisan vendor:publish --tag="telegraph-migrations"
php artisan migrate
```

3. **Опубликуйте конфигурацию (опционально):**
```bash
php artisan vendor:publish --tag="telegraph-config"
```

4. **Выполните миграцию для таблицы telegram_bots (если еще не выполнена):**
```bash
php artisan migrate
```

## Использование

После установки вы можете использовать:

- **ExtendedTelegraph** - расширенный класс с дополнительными методами Telegram Bot API
- **TelegramBotService** - сервис для удобной работы с ботами

Подробная документация в файле `README_TELEGRAPH.md`

## API Endpoints

После установки доступны следующие endpoints:

- `GET /api/v1/bots/{id}/info` - Получить информацию о боте через Telegram API
- `POST /api/v1/bots/{id}/send-message` - Отправить тестовое сообщение

Пример запроса для отправки сообщения:
```json
POST /api/v1/bots/1/send-message
{
    "chat_id": "123456789",
    "message": "Привет из API!"
}
```

