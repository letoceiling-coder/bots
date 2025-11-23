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
                            {--skip-pull : Пропустить pull (не получать изменения с сервера)}
                            {--force : Принудительная отправка (опасно! перезапишет изменения на сервере)}
                            {--no-ssl-verify : Отключить проверку SSL сертификата (только для локальной разработки)}
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
            
            if (!$this->option('skip-push') && !$this->option('skip-pull')) {
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
                        $stdOutput = $pullProcess->getOutput();
                        $fullOutput = $stdOutput . "\n" . $errorOutput;
                        
                        // Если rebase не удался из-за конфликтов, пробуем обычный pull
                        if (strpos($fullOutput, 'conflict') !== false || 
                            strpos($fullOutput, 'CONFLICT') !== false ||
                            strpos($fullOutput, 'merge conflict') !== false) {
                            $this->warn('⚠️  Обнаружены конфликты при rebase. Пробуем обычный pull...');
                            
                            // Отменяем rebase
                            $abortProcess = new SymfonyProcess(['git', 'rebase', '--abort']);
                            $abortProcess->run();
                            
                            // Делаем обычный pull
                            $pullProcess = new SymfonyProcess(['git', 'pull', 'origin', $pushBranch]);
                            $pullProcess->setTimeout(300);
                            $pullProcess->run();
                            
                            if (!$pullProcess->isSuccessful()) {
                                $pullError = $pullProcess->getErrorOutput();
                                $pullOutput = $pullProcess->getOutput();
                                
                                // Проверяем, есть ли конфликты
                                $conflictFiles = [];
                                $statusCheck = new SymfonyProcess(['git', 'status', '--short']);
                                $statusCheck->run();
                                $statusShort = $statusCheck->getOutput();
                                
                                // Ищем файлы с конфликтами (UU, AA, DD)
                                foreach (explode("\n", $statusShort) as $line) {
                                    if (preg_match('/^[A-Z]{2}\s+(.+)$/', $line, $matches)) {
                                        $conflictFiles[] = trim($matches[1]);
                                    }
                                }
                                
                                $this->newLine();
                                $this->error('❌ Обнаружены конфликты при объединении изменений!');
                                $this->newLine();
                                
                                if (!empty($conflictFiles)) {
                                    $this->warn('Файлы с конфликтами:');
                                    foreach ($conflictFiles as $file) {
                                        $this->line("   - {$file}");
                                    }
                                    $this->newLine();
                                }
                                
                                $this->warn('Для разрешения конфликтов выполните:');
                                $this->line("   1. git pull origin {$pushBranch}");
                                $this->line('   2. Откройте файлы с конфликтами и разрешите их');
                                $this->line('   3. git add .');
                                $this->line("   4. git commit -m 'Resolve conflicts'");
                                $this->line("   5. php artisan push:server");
                                $this->newLine();
                                
                                // Если включена опция --force, предлагаем использовать её
                                if ($this->option('force')) {
                                    $this->warn('⚠️  ВНИМАНИЕ: Опция --force перезапишет изменения на сервере!');
                                    if (!$this->confirm('Продолжить с принудительной отправкой?', false)) {
                                        return Command::FAILURE;
                                    }
                                    // Пропускаем pull и переходим к force push
                                } else {
                                    $this->info('💡 Совет: Если вы уверены, что ваши изменения важнее, используйте:');
                                    $this->line("   php artisan push:server --force");
                                    $this->line("   (⚠️  Это перезапишет изменения на сервере!)");
                                    return Command::FAILURE;
                                }
                            } else {
                                $this->info('✅ Изменения объединены');
                            }
                        } else {
                            $this->error('❌ Ошибка при получении обновлений: ' . $errorOutput);
                            return Command::FAILURE;
                        }
                    } else {
                        $this->info('✅ Изменения объединены');
                    }
                } elseif (strpos($statusOutput, 'ahead') !== false && strpos($statusOutput, 'behind') === false) {
                    // Только ahead - можно пушить
                    $this->info('✅ Локальные изменения готовы к отправке');
                }
            } elseif ($this->option('skip-pull')) {
                // Определяем текущую ветку для push
                $currentBranchProcess = new SymfonyProcess(['git', 'branch', '--show-current']);
                $currentBranchProcess->run();
                $currentBranch = trim($currentBranchProcess->getOutput()) ?: $branch;
                $pushBranch = ($currentBranch === $branch) ? $branch : $currentBranch;
                
                $this->warn('⚠️  Пропуск pull - изменения с сервера не получены');
            } else {
                // Определяем текущую ветку для push
                $currentBranchProcess = new SymfonyProcess(['git', 'branch', '--show-current']);
                $currentBranchProcess->run();
                $currentBranch = trim($currentBranchProcess->getOutput()) ?: $branch;
                $pushBranch = ($currentBranch === $branch) ? $branch : $currentBranch;
            }

            // 5. Push в Git
            if (!$this->option('skip-push')) {
                $this->newLine();
                
                // Если используется force, предупреждаем
                if ($this->option('force')) {
                    $this->warn('⚠️  ВНИМАНИЕ: Используется принудительная отправка (--force)');
                    $this->warn('   Это перезапишет изменения на сервере!');
                    $this->newLine();
                }
                
                $this->info("📤 Отправка в Git (ветка: {$pushBranch})...");
                
                // Используем force push если указана опция
                $pushCommand = $this->option('force') 
                    ? ['git', 'push', '--force', 'origin', $pushBranch]
                    : ['git', 'push', 'origin', $pushBranch];
                
                $pushProcess = new SymfonyProcess($pushCommand);
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
                $httpClient = Http::timeout(30);
                
                // Отключаем проверку SSL если указана опция
                if ($this->option('no-ssl-verify')) {
                    $this->warn('⚠️  Проверка SSL сертификата отключена (только для локальной разработки!)');
                    $httpClient = $httpClient->withOptions([
                        'verify' => false,
                    ]);
                }
                
                $response = $httpClient->post("{$serverUrl}/api/deploy", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['message'])) {
                        $this->info('✅ ' . $data['message']);
                    } else {
                        $this->info('✅ Запрос на обновление отправлен на сервер');
                    }
                    
                    if (isset($data['status'])) {
                        if ($data['status'] === 'completed') {
                            $this->info('   ✅ Статус: Обновление выполнено успешно');
                            if (isset($data['branch'])) {
                                $this->line("   Ветка: {$data['branch']}");
                            }
                        } elseif ($data['status'] === 'queued') {
                            $this->info('   ⏳ Статус: Ожидание выполнения');
                        } elseif ($data['status'] === 'running') {
                            $this->info('   🔄 Статус: Выполняется обновление');
                            if (isset($data['pid'])) {
                                $this->line("   PID процесса: {$data['pid']}");
                            }
                            if (isset($data['log_file'])) {
                                $this->line("   Лог файл: {$data['log_file']}");
                            }
                        }
                    }
                    
                    $this->newLine();
                    $this->info('🎉 Готово! Изменения отправлены и применены на сервере.');
                } else {
                    $statusCode = $response->status();
                    $body = $response->body();
                    
                    // Пытаемся получить JSON данные
                    $errorData = null;
                    try {
                        $errorData = $response->json();
                    } catch (\Exception $e) {
                        // Если не JSON, используем тело ответа как есть
                    }
                    
                    $this->error("❌ Ошибка при отправке запроса на сервер (HTTP {$statusCode})");
                    
                    // Показываем сообщение из JSON если есть
                    if ($errorData && isset($errorData['message'])) {
                        $this->error('   ' . $errorData['message']);
                    } elseif ($errorData && isset($errorData['error'])) {
                        $this->error('   ' . $errorData['error']);
                    } elseif (!empty($body)) {
                        // Показываем тело ответа если есть (обрезаем для читаемости)
                        $bodyPreview = strlen($body) > 200 ? substr($body, 0, 200) . '...' : $body;
                        $this->error('   Ответ сервера: ' . $bodyPreview);
                        
                        // Если это HTML (ошибка Laravel), показываем подсказку
                        if (strpos($body, '<!DOCTYPE html>') !== false || strpos($body, '<html') !== false) {
                            $this->warn('');
                            $this->warn('⚠️  Сервер вернул HTML вместо JSON. Это означает, что:');
                            $this->line('   1. Роут не найден или настроен неправильно');
                            $this->line('   2. Запрос обрабатывается не API контроллером');
                            $this->line('   3. На сервере нужно обновить роуты');
                        }
                    } else {
                        $this->error('   Неизвестная ошибка');
                    }
                    
                    // Показываем дополнительные детали
                    if ($errorData && isset($errorData['error'])) {
                        $this->error('   Детали: ' . $errorData['error']);
                    }
                    
                    // Специальные сообщения для разных статус кодов
                    if ($statusCode === 403) {
                        $this->warn('');
                        $this->warn('💡 Это ошибка авторизации. Проверьте:');
                        $this->line('   1. Секретный ключ в .env (DEPLOY_SECRET)');
                        $this->line('   2. Секретный ключ на сервере должен совпадать');
                        $this->line('   3. Используйте: php artisan push:server --secret=YOUR_SECRET');
                    } elseif ($statusCode === 404) {
                        $this->warn('');
                        $this->warn('💡 Endpoint не найден. Проверьте:');
                        $this->line("   1. URL сервера: {$serverUrl}");
                        $this->line('   2. Роут /api/deploy должен быть доступен');
                        $this->line('   3. Убедитесь, что на сервере обновлены роуты: php artisan route:clear');
                    } elseif ($statusCode === 405) {
                        $this->warn('');
                        $this->warn('💡 Метод не разрешен (405). Возможные причины:');
                        $this->line("   1. Роут на сервере настроен неправильно");
                        $this->line("   2. URL сервера: {$serverUrl}/api/deploy");
                        $this->line('   3. Проверьте, что на сервере роут настроен как POST');
                        $this->line('   4. Выполните на сервере: php artisan route:list | grep deploy');
                        $this->line('   5. Очистите кеш роутов: php artisan route:clear && php artisan config:clear');
                    } elseif ($statusCode === 500) {
                        $this->warn('');
                        $this->warn('💡 Внутренняя ошибка сервера. Проверьте логи на сервере.');
                    }
                    
                    return Command::FAILURE;
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->error('❌ Ошибка подключения к серверу: ' . $e->getMessage());
                $this->warn('');
                $this->warn('Возможные причины:');
                $this->line('1. Сервер недоступен');
                $this->line('2. Неправильный URL сервера');
                $this->line('3. Проблемы с сетью или файрволом');
                $this->line('4. Проблемы с SSL сертификатом');
                $this->newLine();
                $this->info('💡 Попробуйте использовать опцию --no-ssl-verify для отключения проверки SSL:');
                $this->line("   php artisan push:server --no-ssl-verify");
                return Command::FAILURE;
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Проверяем, это ли ошибка SSL
                if (strpos($errorMessage, 'SSL') !== false || 
                    strpos($errorMessage, 'certificate') !== false ||
                    strpos($errorMessage, 'cURL error 60') !== false) {
                    $this->error('❌ Ошибка SSL сертификата: ' . $errorMessage);
                    $this->warn('');
                    $this->warn('Это типичная проблема на Windows при работе с самоподписанными сертификатами.');
                    $this->newLine();
                    $this->info('💡 Решение: Используйте опцию --no-ssl-verify:');
                    $this->line("   php artisan push:server --no-ssl-verify");
                    $this->newLine();
                    $this->warn('⚠️  ВНИМАНИЕ: Отключение проверки SSL снижает безопасность!');
                    $this->warn('   Используйте только для локальной разработки.');
                } else {
                    $this->error('❌ Ошибка при отправке запроса на сервер: ' . $errorMessage);
                    $this->warn('');
                    $this->warn('Возможные причины:');
                    $this->line('1. Сервер недоступен');
                    $this->line('2. Неправильный URL сервера');
                    $this->line('3. Проблемы с сетью');
                    $this->line('4. Неправильный секретный ключ');
                }
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

