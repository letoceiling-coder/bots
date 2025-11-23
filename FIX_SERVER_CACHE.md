# 🔧 Исправление: Очистка кеша на сервере

## Проблема
Изменения не применяются, ошибка все еще есть.

## Решение

### 1. Проверьте, что файл обновился на сервере

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html
git pull origin main
```

### 2. Проверьте содержимое файла

```bash
grep -n "baseUrl" app/Services/ExtendedTelegraph.php
```

Должно быть:
```php
protected ?string $baseUrl = 'https://api.telegram.org/bot';
```

Если там `protected string $baseUrl`, значит файл не обновился.

### 3. Очистите все кеши

```bash
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear
php8.2 artisan optimize:clear
php8.2 /home/d/dsc23ytp/.local/bin/composer dump-autoload
```

### 4. Очистите opcache PHP (если включен)

```bash
# Создайте временный файл для очистки opcache
php8.2 -r "opcache_reset();"
```

Или перезапустите PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
```

### 5. Проверьте версию файла

```bash
head -20 app/Services/ExtendedTelegraph.php | grep baseUrl
```

Должно быть `?string`, а не просто `string`.

### 6. Если файл не обновился

Выполните полный deploy:

```bash
php8.2 artisan deploy --force
```

Или вручную обновите файл:

```bash
# Сделайте backup
cp app/Services/ExtendedTelegraph.php app/Services/ExtendedTelegraph.php.bak

# Отредактируйте файл
nano app/Services/ExtendedTelegraph.php
# Измените строку 18 с:
# protected string $baseUrl = 'https://api.telegram.org/bot';
# на:
# protected ?string $baseUrl = 'https://api.telegram.org/bot';
```

## Проверка после исправления

После всех действий проверьте логи:

```bash
tail -f storage/logs/laravel.log
```

Ошибка должна исчезнуть.

