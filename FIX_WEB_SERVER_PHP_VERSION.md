# 🔧 Исправление: Настройка веб-сервера на PHP 8.2

## Проблема
Веб-сервер использует PHP 5.6.40 вместо PHP 8.2, поэтому пакет не загружается.

## Решение

### 1. Проверьте, что пакет установлен

```bash
php8.2 /home/d/dsc23ytp/.local/bin/composer show defstudio/telegraph
```

Если пакет не установлен, установите его:

```bash
php8.2 /home/d/dsc23ytp/.local/bin/composer require defstudio/telegraph --no-dev
```

### 2. Настройте веб-сервер на использование PHP 8.2

#### Для Apache (.htaccess)

Создайте или обновите файл `.htaccess` в `public/`:

```apache
<IfModule mod_php8.c>
    php_value engine On
</IfModule>

<IfModule mod_php.c>
    php_value engine Off
</IfModule>

# Или используйте AddHandler
AddHandler application/x-httpd-php82 .php
```

#### Для Nginx

Обновите конфигурацию Nginx:

```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    # или
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### 3. Проверьте настройки хостинга

Если используете панель управления хостингом (ISPmanager, cPanel и т.д.):

1. Зайдите в настройки домена
2. Выберите PHP версию 8.2
3. Сохраните изменения

### 4. Перезапустите веб-сервер

```bash
# Для Apache
sudo systemctl restart apache2
# или
sudo service apache2 restart

# Для Nginx + PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### 5. Проверьте версию PHP через веб-интерфейс

Создайте файл `public/phpinfo.php`:

```php
<?php
phpinfo();
```

Откройте в браузере: `https://parser-auto.siteaccess.ru/phpinfo.php`

Должна быть видна версия PHP 8.2.x

**Важно**: После проверки удалите файл `phpinfo.php` из соображений безопасности!

### 6. Очистите кеш после настройки

```bash
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear
php8.2 /home/d/dsc23ytp/.local/bin/composer dump-autoload
```

## Альтернатива: Проверьте настройки в панели хостинга

Если используете панель управления хостингом, проверьте настройки PHP для домена `parser-auto.siteaccess.ru` и убедитесь, что выбрана версия PHP 8.2.

