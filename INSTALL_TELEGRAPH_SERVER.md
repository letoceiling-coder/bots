# 🔧 Установка Telegraph на сервере

## Проблема
Пакет `defstudio/telegraph` не установлен на сервере.

## Решение

### Вариант 1: Установить пакет напрямую

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html

# Установить пакет (без --no-dev, эта опция не поддерживается в require)
php8.2 /home/d/dsc23ytp/.local/bin/composer require defstudio/telegraph
```

### Вариант 2: Установить все зависимости из composer.lock

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html

# Установить все зависимости (включая dev для установки, потом можно удалить)
php8.2 /home/d/dsc23ytp/.local/bin/composer install

# Или только production зависимости
php8.2 /home/d/dsc23ytp/.local/bin/composer install --no-dev --optimize-autoloader
```

### Вариант 3: Обновить зависимости

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html

# Обновить все зависимости
php8.2 /home/d/dsc23ytp/.local/bin/composer update --no-dev --optimize-autoloader
```

## После установки

Очистите кеш:

```bash
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear
php8.2 /home/d/dsc23ytp/.local/bin/composer dump-autoload
```

## Проверка

После установки проверьте:

```bash
php8.2 /home/d/dsc23ytp/.local/bin/composer show defstudio/telegraph
```

Должен быть виден пакет `defstudio/telegraph v1.66.0`.

