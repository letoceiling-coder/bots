# Инструкция по обновлению и тестированию исправления callback_query

## Шаг 1: Обновление на сервере

Выполните на сервере:

```bash
cd ~/parser-auto.site-access.ru/public_html

# Получить изменения из репозитория
git pull origin main

# Очистить весь кэш
php8.2 artisan optimize:clear

# Опционально: перезапустить PHP-FPM (если изменения не применяются)
# sudo service php8.2-fpm restart
```

## Шаг 2: Проверка изменений

Проверьте, что файлы обновлены:

```bash
# Проверить, что метод answerCallbackQuery добавлен
grep -n "answerCallbackQuery" app/Services/ExtendedTelegraph.php

# Проверить, что handleCallbackQuery обновлен
grep -n "answerCallbackQuery" app/Services/BotMapHandler.php
```

## Шаг 3: Тестирование бота

1. Откройте бота в Telegram: `@lawyers_decision_bot`
2. Отправьте команду `/start`
3. Должны появиться:
   - Приветственное сообщение
   - Главное меню с кнопками

4. Нажмите на любую кнопку (например, "Создать/изменить/закрыть бизнес")

## Шаг 4: Проверка логов

Проверьте логи на сервере:

```bash
# Смотреть логи в реальном времени
tail -f storage/logs/laravel.log | grep -E "(callback|Callback|callback_query)"

# Или посмотреть последние логи
tail -100 storage/logs/laravel.log | grep -E "(callback|Callback)"
```

### Ожидаемые записи в логах:

1. **При получении callback_query:**
```
[INFO] Telegram webhook received ... "has_callback_query":true
[INFO] Processing Telegram update ... "update_type":"callback_query"
[INFO] Handling callback query ... "callback_data":"3"
```

2. **При ответе на callback_query:**
```
[DEBUG] Answered callback query ... "callback_query_id":"..."
```

3. **При выполнении блока:**
```
[INFO] Target block found for callback ... "target_block_id":"3"
[INFO] Executing block ... "block_id":"3"
```

## Шаг 5: Диагностика проблем

### Если кнопки все еще не работают:

1. **Проверьте, что callback_query доходит до сервера:**
```bash
# Проверить последние 50 строк логов
tail -50 storage/logs/laravel.log

# Если нет записей "Telegram webhook received" с callback_query, 
# значит webhook не настроен или Telegram не отправляет запросы
```

2. **Проверьте настройки webhook:**
```bash
# Получить информацию о webhook
curl "https://api.telegram.org/bot7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM/getWebhookInfo"
```

Должны быть:
- `"url": "https://parser-auto.siteaccess.ru/api/telegram/webhook/1"`
- `"allowed_updates"` должен включать `"callback_query"` или быть пустым (тогда разрешены все)

3. **Если webhook не настроен для callback_query, установите явно:**
```bash
curl -X POST "https://api.telegram.org/bot7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM/setWebhook?url=https://parser-auto.siteaccess.ru/api/telegram/webhook/1&allowed_updates=[\"message\",\"callback_query\"]"
```

### Если в логах есть ошибки:

Проверьте:
- Что токен бота правильный
- Что метод `answerCallbackQuery` работает (должен быть в ExtendedTelegraph.php)
- Что callback_data в кнопках правильный (проверено ранее - все кнопки имеют callback_data)

## Шаг 6: Очистка временных файлов (опционально)

После успешного тестирования можно удалить временные файлы:

```bash
cd ~/parser-auto.site-access.ru/public_html
rm -f CHECK_WEBHOOK.md CHECK_WEBHOOK_STATUS.md FIX_WEBHOOK_EMPTY_PAGE.md FIX_WEBHOOK_ON_SERVER.md INSTALL_WEBHOOK.md SETUP_WEBHOOK.md TEST_WEBHOOK.md check_block_data.php check_start_block.php setup_webhook.sh
```

## Успешный результат

После исправления:
- ✅ Кнопки в главном меню реагируют на клик
- ✅ Появляется индикатор загрузки и сразу исчезает
- ✅ Выполняется соответствующий блок карты
- ✅ В логах видны все этапы обработки callback_query

