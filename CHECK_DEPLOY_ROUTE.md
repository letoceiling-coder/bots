# 🔍 Проверка роута deploy на сервере

## Проблема: HTTP 405 (Method Not Allowed)

Если вы получаете ошибку 405 при отправке запроса на сервер, выполните следующие шаги:

## Шаг 1: Проверьте, что роут существует

На сервере выполните:

```bash
php artisan route:list | grep deploy
```

Должен быть виден роут:
```
POST   api/deploy ................... deploy › DeployController@deploy
```

## Шаг 2: Очистите кеш роутов

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

## Шаг 3: Проверьте файл routes/api.php

Убедитесь, что в файле `routes/api.php` есть:

```php
Route::post('/deploy', [DeployController::class, 'deploy'])->middleware('throttle:10,1');
```

## Шаг 4: Проверьте, что контроллер существует

```bash
php artisan make:controller DeployController
```

Или убедитесь, что файл `app/Http/Controllers/DeployController.php` существует.

## Шаг 5: Проверьте префикс API

В Laravel 11 API роуты автоматически имеют префикс `/api`. 

Проверьте `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    ...
)
```

## Шаг 6: Проверьте доступность endpoint

Попробуйте выполнить запрос напрямую:

```bash
curl -X POST https://parser-auto.siteaccess.ru/api/deploy \
  -H "Content-Type: application/json" \
  -d '{"secret":"YOUR_SECRET","branch":"main"}'
```

## Шаг 7: Проверьте логи

```bash
tail -f storage/logs/laravel.log
```

## Решение

После обновления файлов на сервере:

1. Очистите кеш: `php artisan route:clear && php artisan config:clear`
2. Проверьте роуты: `php artisan route:list | grep deploy`
3. Попробуйте снова: `php artisan push:server --no-ssl-verify --secret=YOUR_SECRET`

