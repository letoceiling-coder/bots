#!/bin/bash
# Скрипт для обновления на сервере с разрешением конфликтов

cd ~/parser-auto.site-access.ru/public_html

echo "Проверка статуса Git..."
git status

echo ""
echo "Сохранение локальных изменений в stash..."
git stash save "Локальные изменения перед обновлением $(date +%Y-%m-%d_%H:%M:%S)"

echo ""
echo "Получение изменений из репозитория..."
git pull origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Обновление успешно!"
    echo ""
    echo "Очистка кэша..."
    php8.2 artisan optimize:clear
    echo ""
    echo "✅ Готово! Изменения применены."
    echo ""
    echo "Примечание: локальные изменения сохранены в stash."
    echo "Если они нужны, выполните: git stash list (посмотреть) или git stash pop (восстановить)"
else
    echo ""
    echo "❌ Ошибка при обновлении. Проверьте конфликты вручную."
    exit 1
fi

