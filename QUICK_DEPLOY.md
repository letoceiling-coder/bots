# Быстрая инструкция по обновлению проекта

## 🚀 Самый простой способ

### На сервере выполните одну команду:

```bash
php artisan deploy --force
```

Эта команда автоматически:
- ✅ Получит обновления из Git
- ✅ Обновит все зависимости
- ✅ Соберет фронтенд
- ✅ Выполнит миграции
- ✅ Очистит и оптимизирует кэш

## 📝 Альтернативные способы

### Вариант 1: Bash скрипт

```bash
chmod +x deploy.sh
./deploy.sh
```

### Вариант 2: Ручные команды

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install --production
npm run build
php artisan migrate --force
php artisan config:clear && php artisan cache:clear
php artisan config:cache && php artisan route:cache
```

## ⚙️ Опции команды deploy

```bash
# Пропустить миграции
php artisan deploy --skip-migrations

# Пропустить сборку фронтенда
php artisan deploy --skip-build

# Пропустить оптимизацию
php artisan deploy --skip-optimize

# Все опции вместе
php artisan deploy --skip-migrations --skip-build --skip-optimize --force
```

## 🔄 Автоматическое обновление через Cron

Добавьте в crontab:

```bash
crontab -e
```

Добавьте строку (обновление каждый день в 3:00):

```
0 3 * * * cd /path/to/project && php artisan deploy --force >> /var/log/deploy.log 2>&1
```

Готово! 🎉

