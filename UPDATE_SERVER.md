# 🔄 Обновление проекта на сервере

## Автоматическое обновление

Если изменения уже отправлены в Git, выполните на сервере:

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html
php8.2 artisan deploy --force
```

## Ручное обновление (если нужно)

### 1. Получить обновления из Git

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html
git pull origin main
```

### 2. Установить зависимости

```bash
php8.2 /home/d/dsc23ytp/.local/bin/composer install --no-dev --optimize-autoloader
```

### 3. Обновить NPM зависимости и собрать фронтенд

```bash
npm install
npm run build
```

### 4. Выполнить миграции

```bash
php8.2 artisan migrate --force
```

### 5. Очистить кеш

```bash
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear
php8.2 /home/d/dsc23ytp/.local/bin/composer dump-autoload
```

### 6. Оптимизировать (опционально)

```bash
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
```

## Проверка после обновления

Проверьте, что все работает:

```bash
# Проверить версию PHP
php8.2 -v

# Проверить установленные пакеты
php8.2 /home/d/dsc23ytp/.local/bin/composer show defstudio/telegraph

# Проверить логи
tail -f storage/logs/laravel.log
```

## Готово!

После выполнения всех шагов проект обновлен на сервере.

