<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bot = \App\Models\Bot::find(1);
$blocks = $bot->blocks ?? [];
$startBlock = collect($blocks)->firstWhere('command', '/start');

echo "=== Блок /start ===\n";
echo json_encode($startBlock, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== method_data ===\n";
echo json_encode($startBlock['method_data'] ?? $startBlock['methodData'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== Текст сообщения ===\n";
$methodData = $startBlock['method_data'] ?? $startBlock['methodData'] ?? [];
echo "text: " . ($methodData['text'] ?? 'ОТСУТСТВУЕТ') . "\n";

