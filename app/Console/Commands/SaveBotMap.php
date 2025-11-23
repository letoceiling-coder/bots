<?php

namespace App\Console\Commands;

use App\Models\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SaveBotMap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:save-map {bot_id=1} {map_file=bot_map_full.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сохранить карту бота из JSON файла в базу данных';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botId = $this->argument('bot_id');
        $mapFile = $this->argument('map_file');
        
        // Если файл не указан абсолютным путем, ищем в корне проекта
        if (!str_starts_with($mapFile, '/') && !preg_match('/^[A-Z]:\\\\/', $mapFile)) {
            $mapFile = base_path($mapFile);
        }
        
        if (!File::exists($mapFile)) {
            $this->error("Файл карты не найден: {$mapFile}");
            return 1;
        }
        
        $this->info("Загрузка карты из файла: {$mapFile}");
        
        try {
            $mapContent = File::get($mapFile);
            $mapData = json_decode($mapContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Ошибка парсинга JSON: " . json_last_error_msg());
                return 1;
            }
            
            if (!isset($mapData['blocks']) || !is_array($mapData['blocks'])) {
                $this->error("В файле карты отсутствует массив blocks");
                return 1;
            }
            
            $blocks = $mapData['blocks'];
            $this->info("Загружено блоков: " . count($blocks));
            
            // Находим бота
            $bot = Bot::find($botId);
            
            if (!$bot) {
                $this->error("Бот с id = {$botId} не найден в базе данных");
                return 1;
            }
            
            $this->info("Найден бот: {$bot->name} (ID: {$bot->id})");
            
            $oldBlocksCount = is_array($bot->blocks) ? count($bot->blocks) : 0;
            $this->info("Текущее количество блоков: {$oldBlocksCount}");
            
            // Обновляем блоки бота
            $bot->update([
                'blocks' => $blocks
            ]);
            
            // Обновляем модель для получения свежих данных
            $bot->refresh();
            
            $this->info("");
            $this->info("✅ Карта успешно сохранена!");
            $this->info("Бот: {$bot->name}");
            $this->info("ID бота: {$bot->id}");
            $this->info("Сохранено блоков: " . count($bot->blocks));
            
            if ($this->option('verbose')) {
                $this->info("");
                $this->info("Список блоков:");
                foreach ($bot->blocks as $index => $block) {
                    $method = $block['method'] ?? 'не указан';
                    $label = $block['label'] ?? 'без подписи';
                    $this->line("  " . ($index + 1) . ". [{$method}] {$label} (ID: {$block['id']})");
                }
            }
            
            $this->info("");
            $this->info("🎉 Готово! Карта привязана к боту с id = {$botId}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error("Трассировка:\n" . $e->getTraceAsString());
            }
            return 1;
        }
    }
}

