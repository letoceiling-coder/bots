<?php
// Скрипт для проверки и настройки webhook для callback_query

$token = '7949764871:AAH5cWe3WHQfWMXAX5RyNR_9cXVMejDeAWM';
$webhookUrl = 'https://parser-auto.siteaccess.ru/api/telegram/webhook/1';

// 1. Проверить текущие настройки webhook
echo "=== Проверка текущих настроек webhook ===\n";
$checkUrl = "https://api.telegram.org/bot{$token}/getWebhookInfo";
$response = file_get_contents($checkUrl);
$data = json_decode($response, true);

echo "Текущий webhook URL: " . ($data['result']['url'] ?? 'не установлен') . "\n";
echo "Allowed updates: " . json_encode($data['result']['allowed_updates'] ?? 'все типы') . "\n";
echo "Pending updates: " . ($data['result']['pending_update_count'] ?? 0) . "\n";
echo "\n";

// 2. Установить webhook с явным указанием allowed_updates
echo "=== Установка webhook с поддержкой callback_query ===\n";
$setUrl = "https://api.telegram.org/bot{$token}/setWebhook";
$postData = [
    'url' => $webhookUrl,
    'allowed_updates' => json_encode(['message', 'callback_query', 'edited_message'])
];

$ch = curl_init($setUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
$result = json_decode($response, true);
echo "Success: " . ($result['ok'] ? 'Да' : 'Нет') . "\n";
echo "Description: " . ($result['description'] ?? 'N/A') . "\n";
echo "\n";

// 3. Повторно проверить настройки
echo "=== Проверка обновленных настроек ===\n";
$response = file_get_contents($checkUrl);
$data = json_decode($response, true);

echo "Webhook URL: " . ($data['result']['url'] ?? 'не установлен') . "\n";
echo "Allowed updates: " . json_encode($data['result']['allowed_updates'] ?? 'все типы') . "\n";
echo "\n";

echo "✅ Готово! Теперь webhook настроен для получения callback_query.\n";

