# ⚡ Быстрое исправление Composer

## Используйте правильный путь к composer:

```bash
php8.2 /home/d/dsc23ytp/.local/bin/composer install --no-dev --optimize-autoloader
```

## Полная последовательность обновления:

```bash
# 1. Обновить зависимости
php8.2 /home/d/dsc23ytp/.local/bin/composer install --no-dev --optimize-autoloader

# 2. Обновить NPM
npm install --production

# 3. Собрать фронтенд
npm run build

# 4. Выполнить миграции
php8.2 artisan migrate --force

# 5. Очистить кэш
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear

# 6. Оптимизировать
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
```

## Или используйте переменную:

```bash
COMPOSER=/home/d/dsc23ytp/.local/bin/composer php8.2 $COMPOSER install --no-dev --optimize-autoloader
```

