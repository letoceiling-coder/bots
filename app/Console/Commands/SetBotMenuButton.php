<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\BotController;
use App\Models\Bot;
use App\Services\ExtendedTelegraph;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SetBotMenuButton extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:set-menu-button 
                            {--bot-id= : ID конкретного бота (если не указан, обработаются все боты)}
                            {--update-commands : Обновить команды из блоков перед установкой кнопки меню}
                            {--type=commands : Тип кнопки меню (commands, web_app, default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Установить кнопку меню для существующих ботов

Примеры использования:
  php artisan bot:set-menu-button                    # Установить кнопку меню для всех ботов
  php artisan bot:set-menu-button --bot-id=1        # Установить кнопку меню для бота с ID=1
  php artisan bot:set-menu-button --update-commands  # Обновить команды и установить кнопку меню
  php artisan bot:set-menu-button --type=commands    # Установить кнопку меню типа "commands"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botId = $this->option('bot-id');
        $updateCommands = $this->option('update-commands');
        $menuType = $this->option('type');

        // Валидация типа кнопки меню
        if (!in_array($menuType, ['commands', 'web_app', 'default'])) {
            $this->error("Неверный тип кнопки меню: {$menuType}. Допустимые значения: commands, web_app, default");
            return 1;
        }

        // Получаем ботов для обработки
        if ($botId) {
            $bots = Bot::where('id', $botId)->get();
            if ($bots->isEmpty()) {
                $this->error("Бот с ID {$botId} не найден");
                return 1;
            }
        } else {
            $bots = Bot::all();
            if ($bots->isEmpty()) {
                $this->warn('В базе данных нет ботов');
                return 0;
            }
        }

        $this->info("Найдено ботов для обработки: {$bots->count()}");
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        $bar = $this->output->createProgressBar($bots->count());
        $bar->start();

        foreach ($bots as $bot) {
            try {
                // Проверяем, активен ли бот и есть ли токен
                if (!$bot->is_active) {
                    $this->newLine();
                    $this->warn("Бот '{$bot->name}' (ID: {$bot->id}) неактивен, пропускаем...");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                if (empty($bot->token)) {
                    $this->newLine();
                    $this->warn("У бота '{$bot->name}' (ID: {$bot->id}) отсутствует токен, пропускаем...");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Обновляем команды, если указана опция
                if ($updateCommands) {
                    $this->newLine();
                    $this->line("Обновление команд для бота '{$bot->name}' (ID: {$bot->id})...");
                    
                    // Используем рефлексию для вызова protected метода
                    $botController = new BotController();
                    $reflection = new \ReflectionClass($botController);
                    $method = $reflection->getMethod('setBotCommandsFromBlocks');
                    $method->setAccessible(true);
                    $method->invoke($botController, $bot->fresh());
                    
                    $this->line("✅ Команды обновлены");
                }

                // Устанавливаем кнопку меню
                $telegraph = new ExtendedTelegraph();
                $telegraph->setBot($bot);

                $menuButton = ['type' => $menuType];
                
                // Если тип web_app, нужно указать text и url
                if ($menuType === 'web_app') {
                    $this->warn("Для типа 'web_app' требуется указать 'text' и 'url'. Используется тип 'commands'.");
                    $menuButton = ['type' => 'commands'];
                }

                $result = $telegraph->setChatMenuButton($menuButton, null);

                if (isset($result['ok']) && $result['ok'] === true) {
                    $successCount++;
                    Log::info('Bot menu button set via command', [
                        'bot_id' => $bot->id,
                        'bot_name' => $bot->name,
                        'menu_type' => $menuType,
                    ]);
                } else {
                    $errorCount++;
                    $errorMessage = $result['description'] ?? 'Unknown error';
                    $this->newLine();
                    $this->error("Ошибка установки кнопки меню для бота '{$bot->name}' (ID: {$bot->id}): {$errorMessage}");
                    Log::error('Failed to set bot menu button via command', [
                        'bot_id' => $bot->id,
                        'bot_name' => $bot->name,
                        'result' => $result,
                    ]);
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("Ошибка при обработке бота '{$bot->name}' (ID: {$bot->id}): {$e->getMessage()}");
                Log::error('Error setting bot menu button via command', [
                    'bot_id' => $bot->id,
                    'bot_name' => $bot->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Выводим итоговую статистику
        $this->info('Результаты:');
        $this->line("✅ Успешно: {$successCount}");
        if ($errorCount > 0) {
            $this->error("❌ Ошибок: {$errorCount}");
        }
        if ($skippedCount > 0) {
            $this->warn("⏭️  Пропущено: {$skippedCount}");
        }

        return $errorCount > 0 ? 1 : 0;
    }
}

