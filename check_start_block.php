<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bot = \App\Models\Bot::find(1);
$blocks = $bot->blocks ?? [];
$startBlock = collect($blocks)->firstWhere('command', '/start');

echo json_encode([
    'has_start_block' => !empty($startBlock),
    'start_block_id' => $startBlock['id'] ?? null,
    'start_block_method' => $startBlock['method'] ?? null,
    'start_block_label' => $startBlock['label'] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

