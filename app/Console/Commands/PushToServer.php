<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class PushToServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:server 
                            {--message= : Сообщение для коммита}
                            {--skip-commit : Пропустить коммит (только push)}
                            {--skip-push : Пропустить push (только коммит)}
                            {--branch=main : Ветка для push}
                            {--server=https://parser-auto.siteaccess.ru : URL сервера}
                            {--secret= : Секретный ключ для авторизации}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправить изменения в Git и запросить обновление на сервере';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Отправка изменений на сервер...');

        // Проверка Git репозитория
        if (!is_dir(base_path('.git'))) {
            $this->error('❌ Git репозиторий не найден!');
            $this->warn('Инициализируйте репозиторий: git init');
            return Command::FAILURE;
        }

        $branch = $this->option('branch') ?: 'main';
        $serverUrl = rtrim($this->option('server'), '/');
        $secret = $this->option('secret') ?: config('app.deploy_secret', env('DEPLOY_SECRET'));

        if (!$secret) {
            $this->warn('⚠️  Секретный ключ не указан!');
            $this->warn('Используйте --secret=KEY или установите DEPLOY_SECRET в .env');
            if (!$this->confirm('Продолжить без секретного ключа?', false)) {
                return Command::FAILURE;
            }
        }

        try {
            // 1. Проверка статуса Git
            $this->newLine();
            $this->info('📋 Проверка статуса Git...');
            
            $statusProcess = new SymfonyProcess(['git', 'status', '--porcelain']);
            $statusProcess->run();
            $statusOutput = trim($statusProcess->getOutput());

            if (empty($statusOutput) && !$this->option('skip-commit')) {
                $this->warn('⚠️  Нет изменений для коммита');
                if (!$this->confirm('Продолжить отправку на сервер?', true)) {
                    return Command::FAILURE;
                }
            }

            // 2. Добавление файлов в Git
            if (!$this->option('skip-commit')) {
                $this->newLine();
                $this->info('📦 Добавление файлов в Git...');
                
                $addProcess = new SymfonyProcess(['git', 'add', '.']);
                $addProcess->run();
                
                if (!$addProcess->isSuccessful()) {
                    $this->error('❌ Ошибка при добавлении файлов: ' . $addProcess->getErrorOutput());
                    return Command::FAILURE;
                }
                
                $this->info('✅ Файлы добавлены');

                // 3. Создание коммита
                $this->newLine();
                $this->info('💾 Создание коммита...');
                
                $message = $this->option('message') ?: 'Update from local development';
                
                $commitProcess = new SymfonyProcess(['git', 'commit', '-m', $message]);
                $commitProcess->run();
                
                if (!$commitProcess->isSuccessful()) {
                    $errorOutput = $commitProcess->getErrorOutput();
                    // Игнорируем ошибку "nothing to commit"
                    if (strpos($errorOutput, 'nothing to commit') === false) {
                        $this->error('❌ Ошибка при создании коммита: ' . $errorOutput);
                        return Command::FAILURE;
                    }
                    $this->warn('⚠️  Нет изменений для коммита');
                } else {
                    $this->info('✅ Коммит создан: ' . $message);
                }
            }

            // 4. Push в Git
            if (!$this->option('skip-push')) {
                $this->newLine();
                $this->info("📤 Отправка в Git (ветка: {$branch})...");
                
                // Определяем текущую ветку, если не указана
                $currentBranchProcess = new SymfonyProcess(['git', 'branch', '--show-current']);
                $currentBranchProcess->run();
                $currentBranch = trim($currentBranchProcess->getOutput()) ?: $branch;
                
                // Если указанная ветка отличается от текущей, используем текущую
                $pushBranch = ($currentBranch === $branch) ? $branch : $currentBranch;
                
                if ($pushBranch !== $branch) {
                    $this->warn("⚠️  Текущая ветка: {$currentBranch}, будет отправлена вместо {$branch}");
                }
                
                $pushProcess = new SymfonyProcess(['git', 'push', 'origin', $pushBranch]);
                $pushProcess->setTimeout(300); // 5 минут таймаут
                $pushProcess->run();
                
                if (!$pushProcess->isSuccessful()) {
                    $errorOutput = $pushProcess->getErrorOutput();
                    $this->error('❌ Ошибка при отправке в Git: ' . $errorOutput);
                    $this->warn('');
                    $this->warn('Возможные причины:');
                    $this->line('1. Нет доступа к репозиторию');
                    $this->line('2. Ветка не существует на remote');
                    $this->line('3. Нет подключения к интернету');
                    $this->line('4. Нужно установить upstream: git push -u origin ' . $pushBranch);
                    return Command::FAILURE;
                }
                
                $this->info('✅ Изменения отправлены в Git');
            }

            // 5. Отправка запроса на сервер для обновления
            $this->newLine();
            $this->info("🌐 Отправка запроса на сервер: {$serverUrl}...");
            
            $payload = [
                'branch' => $branch,
                'timestamp' => now()->toIso8601String(),
            ];

            if ($secret) {
                $payload['secret'] = $secret;
            }

            try {
                $response = Http::timeout(30)->post("{$serverUrl}/api/deploy", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->info('✅ Запрос на обновление отправлен на сервер');
                    
                    if (isset($data['message'])) {
                        $this->line('   ' . $data['message']);
                    }
                    
                    if (isset($data['status']) && $data['status'] === 'queued') {
                        $this->info('   Статус: Ожидание выполнения');
                    } elseif (isset($data['status']) && $data['status'] === 'running') {
                        $this->info('   Статус: Выполняется обновление');
                    }
                } else {
                    $errorData = $response->json();
                    $errorMessage = $errorData['message'] ?? 'Неизвестная ошибка';
                    
                    $this->error('❌ Ошибка при отправке запроса на сервер');
                    $this->error('   ' . $errorMessage);
                    
                    if (isset($errorData['error'])) {
                        $this->error('   Детали: ' . $errorData['error']);
                    }
                    
                    return Command::FAILURE;
                }
            } catch (\Exception $e) {
                $this->error('❌ Ошибка при отправке запроса на сервер: ' . $e->getMessage());
                $this->warn('');
                $this->warn('Возможные причины:');
                $this->line('1. Сервер недоступен');
                $this->line('2. Неправильный URL сервера');
                $this->line('3. Проблемы с сетью');
                $this->line('4. Неправильный секретный ключ');
                return Command::FAILURE;
            }

            $this->newLine();
            $this->info('🎉 Процесс завершен успешно!');
            $this->line('');
            $this->line('Следующие шаги:');
            $this->line('1. Дождитесь завершения обновления на сервере');
            $this->line('2. Проверьте логи на сервере при необходимости');
            $this->line('3. Обновите страницу в браузере (Ctrl+F5)');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

