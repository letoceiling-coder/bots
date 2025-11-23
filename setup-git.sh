#!/bin/bash

# Скрипт для инициализации Git репозитория на сервере
# Использование: bash setup-git.sh

set -e

echo "🔧 Настройка Git репозитория на сервере..."

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
    echo -e "${YELLOW}Установите Git: sudo apt-get install git${NC}"
    exit 1
fi

# Проверка, не инициализирован ли уже репозиторий
if [ -d ".git" ]; then
    echo -e "${YELLOW}⚠️  Git репозиторий уже инициализирован${NC}"
    read -p "Продолжить настройку? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
else
    # Инициализация Git репозитория
    echo -e "${YELLOW}📦 Инициализация Git репозитория...${NC}"
    git init
    echo -e "${GREEN}✅ Git репозиторий инициализирован${NC}"
fi

# Добавление всех файлов
echo -e "${YELLOW}📝 Добавление файлов в репозиторий...${NC}"
git add .

# Создание первого commit
echo -e "${YELLOW}💾 Создание первого commit...${NC}"
git commit -m "Initial commit from server" || echo -e "${YELLOW}⚠️  Нет изменений для commit${NC}"

# Настройка remote
echo -e "${YELLOW}🔗 Настройка подключения к GitHub...${NC}"
echo "Введите URL вашего GitHub репозитория:"
echo "Пример: https://github.com/letoceiling-coder/bots.git"
read -p "URL: " REPO_URL

if [ -z "$REPO_URL" ]; then
    echo -e "${RED}❌ URL репозитория не указан${NC}"
    exit 1
fi

# Удаление существующего remote (если есть)
git remote remove origin 2>/dev/null || true

# Добавление нового remote
git remote add origin "$REPO_URL"
echo -e "${GREEN}✅ Remote добавлен: $REPO_URL${NC}"

# Проверка подключения
echo -e "${YELLOW}🔍 Проверка подключения к репозиторию...${NC}"
git fetch origin

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Ошибка подключения к репозиторию${NC}"
    echo -e "${YELLOW}Возможные причины:${NC}"
    echo "1. Неверный URL репозитория"
    echo "2. Нет доступа к репозиторию (проверьте права доступа)"
    echo "3. Нужна аутентификация (настройте SSH ключи или используйте токен)"
    exit 1
fi

# Настройка ветки
echo -e "${YELLOW}🌿 Настройка ветки main...${NC}"
git branch -M main 2>/dev/null || true

# Попытка связать с удаленной веткой
echo -e "${YELLOW}📥 Получение информации о ветках...${NC}"
git fetch origin main 2>/dev/null || git fetch origin master 2>/dev/null || true

# Проверка, существует ли удаленная ветка main
if git ls-remote --heads origin main | grep -q main; then
    REMOTE_BRANCH="main"
elif git ls-remote --heads origin master | grep -q master; then
    REMOTE_BRANCH="master"
    git branch -M master 2>/dev/null || true
else
    REMOTE_BRANCH="main"
fi

echo -e "${GREEN}✅ Настройка завершена!${NC}"
echo ""
echo -e "${YELLOW}📋 Следующие шаги:${NC}"
echo "1. Если репозиторий на GitHub пустой, выполните:"
echo "   git push -u origin $REMOTE_BRANCH"
echo ""
echo "2. Если репозиторий на GitHub уже содержит код, выполните:"
echo "   git pull origin $REMOTE_BRANCH --allow-unrelated-histories"
echo "   (или git pull origin $REMOTE_BRANCH --rebase)"
echo ""
echo "3. После синхронизации можно использовать:"
echo "   php artisan deploy --force"


