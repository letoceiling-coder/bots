# Исправление пустой страницы webhook endpoint

## Проблема
URL `https://parser-auto.siteaccess.ru/api/telegram/webhook/1` показывает пустую страницу

## Причины

1. **Изменения не применены на сервере** - код обновлен локально, но не загружен на сервер
2. **Кэш роутов не очищен** - Laravel использует старый кэш роутов
3. **Ошибка в коде** - исключение приводит к пустому ответу
4. **Проблемы с маршрутизацией** - сервер не правильно обрабатывает запрос

## Решение

### Шаг 1: Обновить файлы на сервере

Подключитесь к серверу по SSH и выполните:

```bash
cd /path/to/your/project

# Вариант 1: Использовать команду deploy (если есть)
php artisan deploy --force

# Вариант 2: Ручное обновление
git pull origin main
composer install --no-dev --optimize-autoloader
```

### Шаг 2: Очистить кэш

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Шаг 3: Проверить, что роут зарегистрирован

```bash
php artisan route:list | grep webhook
```

Должна быть строка:
```
GET|POST  api/telegram/webhook/{bot_id}  telegram.webhook › Api\TelegramWebhookController@handle
```

### Шаг 4: Проверить файл контроллера

Убедитесь, что файл `app/Http/Controllers/Api/TelegramWebhookController.php` содержит обработку GET запросов.

### Шаг 5: Проверить логи на ошибки

```bash
tail -n 100 storage/logs/laravel.log | grep -i error
```

### Шаг 6: Проверить доступность endpoint через curl

```bash
curl -v https://parser-auto.siteaccess.ru/api/telegram/webhook/1
```

Должен вернуться JSON ответ, а не пустая страница.

## Что должно быть в ответе

После применения изменений, при открытии URL в браузере или curl должен вернуться JSON:

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

## Быстрая проверка

После обновления файлов и очистки кэша, проверьте:

```bash
# Очистить кэш
php artisan route:clear && php artisan cache:clear && php artisan config:clear

# Проверить роут
php artisan route:list | grep webhook

# Тестовый запрос
curl https://parser-auto.siteaccess.ru/api/telegram/webhook/1
```

Если после этого страница все еще пустая, проверьте:
- Права доступа к файлам
- Логи на наличие ошибок
- Настройки веб-сервера (Apache/Nginx)

