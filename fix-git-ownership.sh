#!/bin/bash

# Скрипт для исправления проблемы с правами доступа Git
# Использование: bash fix-git-ownership.sh

set -e

echo "🔧 Исправление прав доступа Git..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Переход в директорию проекта
cd "$(dirname "$0")"

echo -e "${YELLOW}📂 Текущая директория: $(pwd)${NC}"
echo -e "${YELLOW}👤 Текущий пользователь: $(whoami)${NC}"

# Вариант 1: Добавить директорию в safe.directory (рекомендуется)
echo -e "${YELLOW}🔐 Добавление директории в safe.directory...${NC}"
git config --global --add safe.directory "$(pwd)"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Директория добавлена в safe.directory${NC}"
else
    echo -e "${RED}❌ Ошибка при добавлении директории${NC}"
    exit 1
fi

# Вариант 2: Исправить владельца (если есть права)
echo -e "${YELLOW}🔍 Проверка владельца директории...${NC}"
CURRENT_USER=$(whoami)
DIR_OWNER=$(stat -c '%U' .)

if [ "$CURRENT_USER" != "$DIR_OWNER" ]; then
    echo -e "${YELLOW}⚠️  Владелец директории: $DIR_OWNER${NC}"
    echo -e "${YELLOW}⚠️  Текущий пользователь: $CURRENT_USER${NC}"
    echo -e "${YELLOW}💡 Попробуйте исправить владельца (требуются права sudo):${NC}"
    echo -e "   sudo chown -R $CURRENT_USER:$CURRENT_USER ."
    echo ""
    read -p "Исправить владельца сейчас? (требуется sudo) (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo chown -R $CURRENT_USER:$CURRENT_USER .
        echo -e "${GREEN}✅ Владелец исправлен${NC}"
    fi
else
    echo -e "${GREEN}✅ Владелец директории совпадает с текущим пользователем${NC}"
fi

# Переименовать ветку master в main
echo -e "${YELLOW}🌿 Переименование ветки master в main...${NC}"
git branch -m master main 2>/dev/null || echo -e "${YELLOW}⚠️  Ветка уже называется main или не существует${NC}"

echo -e "${GREEN}✅ Настройка завершена!${NC}"
echo ""
echo -e "${YELLOW}📋 Теперь можно выполнить:${NC}"
echo "   git add ."
echo "   git commit -m 'Initial commit from server'"
echo "   git remote add origin https://YOUR_TOKEN@github.com/letoceiling-coder/bots.git"
echo "   git push -u origin main"


