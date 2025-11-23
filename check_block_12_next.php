<?php
// Проверка блока 12 и следующих блоков

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = App\Models\Bot::find(1);

if (!$bot || !$bot->blocks) {
    echo "Bot not found or has no blocks\n";
    exit(1);
}

$blocks = is_string($bot->blocks) ? json_decode($bot->blocks, true) : $bot->blocks;

// Находим блок 12
$block12 = array_filter($blocks, fn($b) => ($b['id'] ?? null) === '12');
$block12 = reset($block12);

if (!$block12) {
    echo "Block 12 not found\n";
    exit(1);
}

echo "=== Block 12 (Выбор ОПФ) ===\n";
echo "ID: " . ($block12['id'] ?? 'N/A') . "\n";
echo "Label: " . ($block12['label'] ?? 'N/A') . "\n";
echo "Method: " . ($block12['method'] ?? 'N/A') . "\n";
echo "nextBlockId: " . ($block12['nextBlockId'] ?? 'NONE (НУЖНО ДОБАВИТЬ!)') . "\n";

$methodData = $block12['methodData'] ?? $block12['method_data'] ?? [];
if (isset($methodData['data_key'])) {
    echo "data_key: " . $methodData['data_key'] . "\n";
} else {
    echo "data_key: NONE (автоматически сгенерируется: 'Выбор_ОПФ')\n";
}

echo "\n=== Buttons ===\n";
if (isset($methodData['inline_keyboard'])) {
    foreach ($methodData['inline_keyboard'] as $i => $row) {
        foreach ($row as $j => $btn) {
            $text = $btn['text'] ?? 'no text';
            $callback = $btn['callback_data'] ?? 'MISSING';
            $target = $btn['target_block_id'] ?? 'none';
            echo "  Button: \"$text\" | callback_data: \"$callback\" | target_block_id: \"$target\"\n";
        }
    }
}

// Показываем следующие блоки после 12
echo "\n=== Possible next blocks ===\n";
$nextBlocks = array_filter($blocks, fn($b) => 
    in_array($b['id'] ?? null, ['13', '14', '15', '16', '17', '18', '19'])
);
foreach ($nextBlocks as $nextBlock) {
    echo "Block ID: " . ($nextBlock['id'] ?? 'N/A') . " | Label: " . ($nextBlock['label'] ?? 'N/A') . " | Method: " . ($nextBlock['method'] ?? 'N/A') . "\n";
}

echo "\n=== Recommendation ===\n";
echo "Нужно добавить nextBlockId для блока 12.\n";
echo "Или добавить target_block_id для каждой кнопки выбора ОПФ.\n";

