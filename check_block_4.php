<?php
// Проверка структуры блока 4 и следующих блоков

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = App\Models\Bot::find(1);

if (!$bot || !$bot->blocks) {
    echo "Bot not found or has no blocks\n";
    exit(1);
}

$blocks = is_string($bot->blocks) ? json_decode($bot->blocks, true) : $bot->blocks;

// Находим блок 4
$block4 = array_filter($blocks, fn($b) => ($b['id'] ?? null) === '4');
$block4 = reset($block4);

if (!$block4) {
    echo "Block 4 not found\n";
    exit(1);
}

echo "=== Block 4 Information ===\n";
echo "ID: " . ($block4['id'] ?? 'N/A') . "\n";
echo "Label: " . ($block4['label'] ?? 'N/A') . "\n";
echo "Method: " . ($block4['method'] ?? 'N/A') . "\n";
echo "nextBlockId: " . ($block4['nextBlockId'] ?? 'NONE') . "\n";

$methodData = $block4['methodData'] ?? $block4['method_data'] ?? [];
if (isset($methodData['text'])) {
    echo "\nText: " . substr($methodData['text'], 0, 100) . "...\n";
}

// Проверяем следующий блок, если указан
$nextBlockId = $block4['nextBlockId'] ?? null;
if ($nextBlockId) {
    $nextBlock = array_filter($blocks, fn($b) => ($b['id'] ?? null) === $nextBlockId);
    $nextBlock = reset($nextBlock);
    
    if ($nextBlock) {
        echo "\n=== Next Block ($nextBlockId) ===\n";
        echo "ID: " . ($nextBlock['id'] ?? 'N/A') . "\n";
        echo "Label: " . ($nextBlock['label'] ?? 'N/A') . "\n";
        echo "Method: " . ($nextBlock['method'] ?? 'N/A') . "\n";
    } else {
        echo "\n⚠ Next block ($nextBlockId) NOT FOUND!\n";
    }
} else {
    echo "\n⚠ No nextBlockId specified for block 4!\n";
    echo "Block 4 needs nextBlockId to continue after sending message.\n";
}

// Показываем все блоки с методом 'question' (вопросы)
echo "\n=== Blocks with method 'question' ===\n";
$questionBlocks = array_filter($blocks, fn($b) => ($b['method'] ?? null) === 'question');
foreach ($questionBlocks as $qBlock) {
    echo "ID: " . ($qBlock['id'] ?? 'N/A') . " | Label: " . ($qBlock['label'] ?? 'N/A') . "\n";
}

