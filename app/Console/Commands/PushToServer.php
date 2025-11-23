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

            // 4. Pull перед push (чтобы получить изменения с сервера)
            $pushBranch = $branch; // Инициализируем переменную
            
            if (!$this->option('skip-push')) {
                $this->newLine();
                $this->info("📥 Получение обновлений из Git перед отправкой...");
                
                // Определяем текущую ветку
                $currentBranchProcess = new SymfonyProcess(['git', 'branch', '--show-current']);
                $currentBranchProcess->run();
                $currentBranch = trim($currentBranchProcess->getOutput()) ?: $branch;
                
                // Если указанная ветка отличается от текущей, используем текущую
                $pushBranch = ($currentBranch === $branch) ? $branch : $currentBranch;
                
                if ($pushBranch !== $branch) {
                    $this->warn("⚠️  Текущая ветка: {$currentBranch}, будет использована вместо {$branch}");
                }
                
                // Сначала делаем fetch
                $fetchProcess = new SymfonyProcess(['git', 'fetch', 'origin']);
                $fetchProcess->setTimeout(60);
                $fetchProcess->run();
                
                if (!$fetchProcess->isSuccessful()) {
                    $this->warn('⚠️  Не удалось получить обновления из Git, продолжаем...');
                } else {
                    $this->info('✅ Обновления получены');
                }
                
                // Проверяем статус относительно remote
                $statusProcess = new SymfonyProcess(['git', 'status', '-sb']);
                $statusProcess->run();
                $statusOutput = $statusProcess->getOutput();
                
                // Если есть изменения на remote (behind), делаем pull
                if (strpos($statusOutput, 'behind') !== false) {
                    $this->warn('⚠️  Обнаружены изменения на сервере. Выполняется pull...');
                    
                    // Пробуем сначала с rebase (более чистая история)
                    $pullProcess = new SymfonyProcess(['git', 'pull', '--rebase', 'origin', $pushBranch]);
                    $pullProcess->setTimeout(300);
                    $pullProcess->run();
                    
                    if (!$pullProcess->isSuccessful()) {
                        $errorOutput = $pullProcess->getErrorOutput();
                        
                        // Если rebase не удался из-за конфликтов, пробуем обычный pull
                        if (strpos($errorOutput, 'conflict') !== false || 
                            strpos($errorOutput, 'CONFLICT') !== false) {
                            $this->warn('⚠️  Обнаружены конфликты при rebase. Пробуем обычный pull...');
                            
                            // Отменяем rebase
                            $abortProcess = new SymfonyProcess(['git', 'rebase', '--abort']);
                            $abortProcess->run();
                            
                            // Делаем обычный pull
                            $pullProcess = new SymfonyProcess(['git', 'pull', 'origin', $pushBranch]);
                            $pullProcess->setTimeout(300);
                            $pullProcess->run();
                            
                            if (!$pullProcess->isSuccessful()) {
                                $this->error('❌ Ошибка при получении обновлений: ' . $pullProcess->getErrorOutput());
                                $this->warn('');
                                $this->warn('Необходимо разрешить конфликты вручную:');
                                $this->line("   git pull origin {$pushBranch}");
                                $this->line('   # Разрешите конфликты');
                                $this->line("   git push origin {$pushBranch}");
                                return Command::FAILURE;
                            }
                        } else {
                            $this->error('❌ Ошибка при получении обновлений: ' . $errorOutput);
                            return Command::FAILURE;
                        }
                    }
                    
                    $this->info('✅ Изменения объединены');
                } elseif (strpos($statusOutput, 'ahead') !== false && strpos($statusOutput, 'behind') === false) {
                    // Только ahead - можно пушить
                    $this->info('✅ Локальные изменения готовы к отправке');
                }
            }

            // 5. Push в Git
            if (!$this->option('skip-push')) {
                $this->newLine();
                $this->info("📤 Отправка в Git (ветка: {$pushBranch})...");
                
                $pushProcess = new SymfonyProcess(['git', 'push', 'origin', $pushBranch]);
                $pushProcess->setTimeout(300); // 5 минут таймаут
                $pushProcess->run();
                
                if (!$pushProcess->isSuccessful()) {
                    $errorOutput = $pushProcess->getErrorOutput();
                    
                    // Проверяем, нужно ли установить upstream
                    if (strpos($errorOutput, 'no upstream branch') !== false || 
                        strpos($errorOutput, 'set upstream') !== false ||
                        strpos($errorOutput, 'upstream') !== false) {
                        $this->warn('⚠️  Ветка не имеет upstream. Устанавливаем...');
                        
                        $setUpstreamProcess = new SymfonyProcess([
                            'git', 
                            'push', 
                            '-u', 
                            'origin', 
                            $pushBranch
                        ]);
                        $setUpstreamProcess->setTimeout(300);
                        $setUpstreamProcess->run();
                        
                        if ($setUpstreamProcess->isSuccessful()) {
                            $this->info('✅ Upstream установлен, изменения отправлены');
                        } else {
                            $this->error('❌ Ошибка при установке upstream: ' . $setUpstreamProcess->getErrorOutput());
                            return Command::FAILURE;
                        }
                    } elseif (strpos($errorOutput, 'rejected') !== false && 
                              strpos($errorOutput, 'fetch first') !== false) {
                        // Ошибка "rejected - fetch first" - нужно еще раз попробовать pull
                        $this->warn('⚠️  Обнаружены новые изменения на сервере. Повторная попытка pull...');
                        
                        $pullProcess = new SymfonyProcess(['git', 'pull', 'origin', $pushBranch]);
                        $pullProcess->setTimeout(300);
                        $pullProcess->run();
                        
                        if ($pullProcess->isSuccessful()) {
                            // Пробуем push снова
                            $pushProcess = new SymfonyProcess(['git', 'push', 'origin', $pushBranch]);
                            $pushProcess->setTimeout(300);
                            $pushProcess->run();
                            
                            if ($pushProcess->isSuccessful()) {
                                $this->info('✅ Изменения отправлены в Git после повторного pull');
                            } else {
                                $this->error('❌ Ошибка при отправке после pull: ' . $pushProcess->getErrorOutput());
                                return Command::FAILURE;
                            }
                        } else {
                            $this->error('❌ Ошибка при повторном pull: ' . $pullProcess->getErrorOutput());
                            $this->warn('');
                            $this->warn('Необходимо разрешить конфликты вручную:');
                            $this->line("   git pull origin {$pushBranch}");
                            $this->line('   # Разрешите конфликты');
                            $this->line("   git push origin {$pushBranch}");
                            return Command::FAILURE;
                        }
                    } else {
                        $this->error('❌ Ошибка при отправке в Git: ' . $errorOutput);
                        $this->warn('');
                        $this->warn('Возможные причины:');
                        $this->line('1. Нет доступа к репозиторию');
                        $this->line('2. Ветка не существует на remote');
                        $this->line('3. Нет подключения к интернету');
                        $this->line('4. Конфликты, которые нужно разрешить вручную');
                        $this->line('');
                        $this->line('Попробуйте выполнить вручную:');
                        $this->line("   git pull origin {$pushBranch}");
                        $this->line("   git push origin {$pushBranch}");
                        return Command::FAILURE;
                    }
                } else {
                    $this->info('✅ Изменения отправлены в Git');
                }
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

