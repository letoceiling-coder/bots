# ⚡ Быстрое обновление Node.js

## Выполните на сервере:

```bash
# 1. Установить nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# 2. Загрузить nvm
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# 3. Установить Node.js 20
nvm install 20
nvm use 20
nvm alias default 20

# 4. Проверить версию
node -v

# 5. Переустановить зависимости
npm install

# 6. Собрать фронтенд
npm run build
```

## ✅ После этого

Продолжите обновление:

```bash
# Миграции
php8.2 artisan migrate --force

# Очистить кэш
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear

# Оптимизировать
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
```


