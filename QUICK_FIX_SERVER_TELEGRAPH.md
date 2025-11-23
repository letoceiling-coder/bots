# ⚡ Быстрое исправление: Telegraph не найден на сервере

## Проблема
```
Class "DefStudio\Telegraph\Telegraph" not found
```

## Быстрое решение

Выполните на сервере:

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html
composer install
```

Или если нужно обновить только этот пакет:

```bash
composer update defstudio/telegraph --no-dev
```

После установки очистите кеш:

```bash
php8.2 artisan config:clear
php8.2 artisan cache:clear
composer dump-autoload
```

## Автоматическое исправление

Или просто запустите команду deploy (она установит все зависимости):

```bash
php8.2 artisan deploy --force
```

## Проверка

После установки проверьте:

```bash
composer show defstudio/telegraph
```

Должен быть виден пакет `defstudio/telegraph v1.66.0`.

## Готово!

После этого ошибка должна исчезнуть.

