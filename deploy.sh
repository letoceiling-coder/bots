#!/bin/bash

# Скрипт для обновления проекта на сервере из Git
# Использование: ./deploy.sh или bash deploy.sh

set -e  # Остановить выполнение при ошибке

echo "🚀 Начало обновления проекта..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Переход в директорию проекта
cd "$(dirname "$0")"

echo -e "${YELLOW}📂 Текущая директория: $(pwd)${NC}"

# Проверка наличия Git
if ! command -v git &> /dev/null; then
    echo -e "${RED}❌ Git не установлен!${NC}"
    exit 1
fi

# 1. Получение обновлений из Git
echo -e "${YELLOW}📥 Получение обновлений из Git...${NC}"
git fetch origin
git pull origin main

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Ошибка при получении обновлений из Git${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Обновления получены${NC}"

# 2. Обновление Composer зависимостей
if [ -f "composer.json" ]; then
    echo -e "${YELLOW}📦 Обновление Composer зависимостей...${NC}"
    composer install --no-dev --optimize-autoloader
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Ошибка при обновлении Composer зависимостей${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Composer зависимости обновлены${NC}"
fi

# 3. Обновление NPM зависимостей и сборка фронтенда
if [ -f "package.json" ]; then
    echo -e "${YELLOW}📦 Обновление NPM зависимостей...${NC}"
    npm install --production
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Ошибка при обновлении NPM зависимостей${NC}"
        exit 1
    fi
    
    echo -e "${YELLOW}🔨 Сборка фронтенда...${NC}"
    npm run build
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Ошибка при сборке фронтенда${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Фронтенд собран${NC}"
fi

# 4. Выполнение миграций
if [ -f "artisan" ]; then
    echo -e "${YELLOW}🗄️  Выполнение миграций...${NC}"
    php artisan migrate --force
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Ошибка при выполнении миграций${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Миграции выполнены${NC}"
fi

# 5. Очистка кэша
if [ -f "artisan" ]; then
    echo -e "${YELLOW}🧹 Очистка кэша...${NC}"
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    
    echo -e "${GREEN}✅ Кэш очищен${NC}"
fi

# 6. Оптимизация (опционально)
if [ -f "artisan" ]; then
    echo -e "${YELLOW}⚡ Оптимизация приложения...${NC}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo -e "${GREEN}✅ Приложение оптимизировано${NC}"
fi

# 7. Установка прав на директории (если нужно)
echo -e "${YELLOW}🔐 Установка прав доступа...${NC}"
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo -e "${GREEN}✅ Права доступа установлены${NC}"

echo -e "${GREEN}🎉 Обновление проекта завершено успешно!${NC}"

