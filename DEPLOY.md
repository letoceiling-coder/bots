# Инструкция по обновлению проекта на сервере

Существует несколько способов обновления проекта на сервере из Git репозитория.

## Способ 1: Использование Artisan команды (Рекомендуется)

### На сервере выполните:

```bash
php artisan deploy
```

### Опции команды:

```bash
# Пропустить миграции
php artisan deploy --skip-migrations

# Пропустить сборку фронтенда
php artisan deploy --skip-build

# Пропустить оптимизацию
php artisan deploy --skip-optimize

# Принудительное выполнение без подтверждения
php artisan deploy --force
```

### Что делает команда:

1. ✅ Получает обновления из Git (`git pull origin main`)
2. ✅ Обновляет Composer зависимости
3. ✅ Обновляет NPM зависимости
4. ✅ Собирает фронтенд (`npm run build`)
5. ✅ Выполняет миграции базы данных
6. ✅ Очищает кэш приложения
7. ✅ Оптимизирует приложение

## Способ 2: Использование Bash скрипта

### На сервере выполните:

```bash
# Сделать скрипт исполняемым (один раз)
chmod +x deploy.sh

# Запустить скрипт
./deploy.sh
```

или

```bash
bash deploy.sh
```

### Что делает скрипт:

1. ✅ Получает обновления из Git
2. ✅ Обновляет Composer зависимости
3. ✅ Обновляет NPM зависимости и собирает фронтенд
4. ✅ Выполняет миграции
5. ✅ Очищает кэш
6. ✅ Оптимизирует приложение
7. ✅ Устанавливает права доступа на директории

## Способ 3: Ручное обновление

Если нужно выполнить обновление вручную:

```bash
# 1. Перейти в директорию проекта
cd /path/to/your/project

# 2. Получить обновления
git fetch origin
git pull origin main

# 3. Обновить зависимости
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# 4. Выполнить миграции
php artisan migrate --force

# 5. Очистить кэш
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 6. Оптимизировать (опционально)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Настройка автоматического обновления

### Вариант 1: Cron задача

Добавьте в crontab для автоматического обновления:

```bash
# Редактировать crontab
crontab -e

# Добавить строку (обновление каждый день в 3:00)
0 3 * * * cd /path/to/your/project && php artisan deploy --force >> /var/log/deploy.log 2>&1
```

### Вариант 2: Webhook (GitHub Actions / GitLab CI)

Создайте endpoint для автоматического обновления при push в репозиторий:

```php
// routes/web.php
Route::post('/deploy', function () {
    // Проверка секретного ключа
    if (request('secret') !== config('app.deploy_secret')) {
        abort(403);
    }
    
    Artisan::call('deploy', ['--force' => true]);
    
    return response()->json(['status' => 'success']);
})->middleware('throttle:10,1');
```

Затем настройте webhook в GitHub/GitLab на этот URL.

### Вариант 3: SSH команда

Если у вас есть доступ по SSH, можно выполнить команду удаленно:

```bash
ssh user@server "cd /path/to/project && php artisan deploy --force"
```

## Требования

- Git должен быть установлен на сервере
- Composer должен быть установлен
- Node.js и npm должны быть установлены
- PHP >= 8.2
- Права на выполнение команд в директории проекта

## Устранение проблем

### Ошибка "Permission denied"

```bash
# Установить права на скрипт
chmod +x deploy.sh

# Установить права на директории
chmod -R 755 storage bootstrap/cache
```

### Ошибка при git pull

```bash
# Проверить настройки Git
git config --list

# Установить имя пользователя и email (если нужно)
git config user.name "Your Name"
git config user.email "your.email@example.com"
```

### Ошибка при composer install

```bash
# Проверить права на директорию vendor
chmod -R 755 vendor

# Очистить кэш Composer
composer clear-cache
```

### Ошибка при npm build

```bash
# Проверить права на node_modules
chmod -R 755 node_modules

# Очистить кэш npm
npm cache clean --force
```

## Безопасность

⚠️ **Важно:**

1. Не храните `.env` файл в Git (он уже в `.gitignore`)
2. Используйте секретный ключ для webhook endpoints
3. Ограничьте доступ к deploy endpoint через middleware
4. Используйте HTTPS для webhook
5. Регулярно обновляйте зависимости для безопасности

## Логирование

Для отслеживания обновлений можно добавить логирование:

```bash
# В скрипте deploy.sh добавьте в начало:
exec > >(tee -a /var/log/deploy.log) 2>&1
```

Или используйте Laravel логирование в Artisan команде.


