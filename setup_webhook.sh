#!/bin/bash

# Токен бота
TOKEN="7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM"

# URL webhook
WEBHOOK_URL="https://parser-auto.siteaccess.ru/api/telegram/webhook/1"

echo "🔧 Установка webhook для бота..."
echo "URL: $WEBHOOK_URL"
echo ""

# Проверка информации о боте
echo "1️⃣ Проверка информации о боте..."
curl -s "https://api.telegram.org/bot${TOKEN}/getMe" | jq '.'
echo ""

# Установка webhook
echo "2️⃣ Установка webhook..."
RESPONSE=$(curl -s -X POST "https://api.telegram.org/bot${TOKEN}/setWebhook?url=${WEBHOOK_URL}")
echo "$RESPONSE" | jq '.'
echo ""

# Проверка статуса webhook
echo "3️⃣ Проверка статуса webhook..."
curl -s "https://api.telegram.org/bot${TOKEN}/getWebhookInfo" | jq '.'

