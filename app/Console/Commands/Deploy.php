<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy 
                            {--skip-migrations : Пропустить выполнение миграций}
                            {--skip-build : Пропустить сборку фронтенда}
                            {--skip-optimize : Пропустить оптимизацию}
                            {--force : Принудительное выполнение без подтверждения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновить проект из Git репозитория на сервере';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Начало обновления проекта...');

        if (!$this->option('force')) {
            if (!$this->confirm('Вы уверены, что хотите обновить проект?', true)) {
                $this->warn('Обновление отменено.');
                return Command::FAILURE;
            }
        }

        $steps = [
            'git' => 'Получение обновлений из Git',
            'composer' => 'Обновление Composer зависимостей',
            'npm' => 'Обновление NPM зависимостей',
            'build' => 'Сборка фронтенда',
            'migrations' => 'Выполнение миграций',
            'cache' => 'Очистка кэша',
            'optimize' => 'Оптимизация приложения',
        ];

        $bar = $this->output->createProgressBar(count($steps));
        $bar->start();

        try {
            // Проверка наличия Git репозитория
            if (!is_dir(base_path('.git'))) {
                $this->newLine();
                $this->error('❌ Git репозиторий не найден!');
                $this->warn('');
                $this->warn('Для настройки Git репозитория выполните:');
                $this->line('1. Инициализируйте репозиторий:');
                $this->line('   git init');
                $this->line('');
                $this->line('2. Добавьте remote:');
                $this->line('   git remote add origin https://github.com/letoceiling-coder/bots.git');
                $this->line('');
                $this->line('3. Или используйте скрипт:');
                $this->line('   bash setup-git.sh');
                $this->line('');
                $this->line('Подробная инструкция в файле SETUP_GIT.md');
                return Command::FAILURE;
            }

            // Проверка наличия remote
            try {
                $remoteCheck = Process::run('git remote get-url origin');
                if (!$remoteCheck->successful()) {
                    $this->newLine();
                    $this->error('❌ Remote origin не настроен!');
                    $this->warn('');
                    $this->warn('Добавьте remote:');
                    $this->line('   git remote add origin https://github.com/letoceiling-coder/bots.git');
                    return Command::FAILURE;
                }
            } catch (\Exception $e) {
                $process = new SymfonyProcess(['git', 'remote', 'get-url', 'origin']);
                $process->run();
                if (!$process->isSuccessful()) {
                    $this->newLine();
                    $this->error('❌ Remote origin не настроен!');
                    $this->warn('');
                    $this->warn('Добавьте remote:');
                    $this->line('   git remote add origin https://github.com/letoceiling-coder/bots.git');
                    return Command::FAILURE;
                }
            }

            // 1. Git pull
            $this->newLine();
            $this->info('📥 Получение обновлений из Git...');
            
            try {
                $result = Process::run('git fetch origin && git pull origin main');
                
                if (!$result->successful()) {
                    $this->error('❌ Ошибка при получении обновлений из Git');
                    $this->error($result->errorOutput());
                    $this->warn('');
                    $this->warn('Возможные причины:');
                    $this->line('1. Нет доступа к репозиторию (проверьте аутентификацию)');
                    $this->line('2. Ветка называется не "main" (проверьте: git branch -a)');
                    $this->line('3. Нет подключения к интернету');
                    return Command::FAILURE;
                }
            } catch (\Exception $e) {
                // Fallback для старых версий Laravel
                $process = new SymfonyProcess(['git', 'fetch', 'origin']);
                $process->run();
                
                if (!$process->isSuccessful()) {
                    $this->error('❌ Ошибка при получении обновлений из Git');
                    $this->error($process->getErrorOutput());
                    return Command::FAILURE;
                }
                
                // Определяем текущую ветку
                $branchProcess = new SymfonyProcess(['git', 'branch', '--show-current']);
                $branchProcess->run();
                $currentBranch = trim($branchProcess->getOutput()) ?: 'main';
                
                $process = new SymfonyProcess(['git', 'pull', 'origin', $currentBranch]);
                $process->run();
                
                if (!$process->isSuccessful()) {
                    $this->error('❌ Ошибка при получении обновлений из Git');
                    $this->error($process->getErrorOutput());
                    return Command::FAILURE;
                }
            }
            
            $this->info('✅ Обновления получены');
            $bar->advance();

            // 2. Composer install
            if (file_exists(base_path('composer.json'))) {
                $this->newLine();
                $this->info('📦 Обновление Composer зависимостей...');
                
                // Определяем версию PHP и путь к composer
                // ВСЕГДА пытаемся использовать php8.2 для composer
                $phpVersion = $this->findPhp82();
                $composerPath = $this->detectComposerPath();
                
                // Если не нашли php8.2, проверяем текущую версию PHP
                if (!$phpVersion) {
                    $currentPhp = PHP_VERSION;
                    if (version_compare($currentPhp, '8.2', '>=')) {
                        // Текущий PHP >= 8.2, можно использовать
                        $phpVersion = null;
                    } else {
                        // Текущий PHP < 8.2, нужно найти php8.2
                        $this->warn('⚠️  Текущая версия PHP: ' . $currentPhp);
                        $this->warn('⚠️  Требуется PHP >= 8.2 для Composer');
                    }
                }
                
                // Формируем команду
                if ($phpVersion && $composerPath) {
                    $composerCommand = "{$phpVersion} {$composerPath}";
                } elseif ($phpVersion) {
                    $composerCommand = "{$phpVersion} " . ($composerPath ?: 'composer');
                } elseif ($composerPath) {
                    $composerCommand = $composerPath;
                } else {
                    $composerCommand = 'composer';
                }
                
                // Всегда используем SymfonyProcess для правильной обработки команды
                $command = [];
                if ($phpVersion) {
                    $command[] = $phpVersion;
                }
                if ($composerPath) {
                    $command[] = $composerPath;
                } else {
                    $command[] = 'composer';
                }
                $command = array_merge($command, ['install', '--no-dev', '--optimize-autoloader']);
                
                try {
                    $process = new SymfonyProcess($command);
                    $process->setTimeout(600); // Увеличиваем таймаут до 10 минут
                    $process->setWorkingDirectory(base_path());
                    $process->run();
                    
                    $output = $process->getOutput();
                    $errorOutput = $process->getErrorOutput();
                    $exitCode = $process->getExitCode();
                    
                    // Объединяем вывод для проверки (composer может выводить в оба потока)
                    $fullOutput = $output . "\n" . $errorOutput;
                    
                    // Проверяем на реальные ошибки (не предупреждения)
                    $hasRealError = false;
                    
                    // Проверяем, есть ли проблемы с версией PHP в выводе
                    if (strpos($fullOutput, 'php version') !== false || 
                        strpos($fullOutput, 'php ^8.2') !== false) {
                        // Проверяем, есть ли блок "Problem" с ошибками версии PHP
                        if (preg_match('/Problem \d+.*?requires php.*?your php version.*?does not satisfy/i', $fullOutput) ||
                            (strpos($fullOutput, 'does not satisfy that requirement') !== false && 
                             preg_match('/Problem \d+/', $fullOutput))) {
                            $hasRealError = true;
                        }
                    }
                    
                    // Если есть реальная ошибка
                    if ($hasRealError) {
                        $this->error('❌ Ошибка при обновлении Composer зависимостей');
                        $this->error($errorOutput ?: $output);
                        $this->warn('');
                        $this->warn('⚠️  Composer использует неправильную версию PHP!');
                        $this->warn('');
                        $this->warn('Попробуйте выполнить вручную:');
                        $this->line("   {$composerCommand} install --no-dev --optimize-autoloader");
                        return Command::FAILURE;
                    }
                    
                    // Проверяем успешность выполнения по содержимому вывода
                    // Composer успешно выполнен, если есть "Package operations" или "Nothing to install"
                    $isSuccessful = strpos($fullOutput, 'Package operations') !== false || 
                                   strpos($fullOutput, 'Nothing to install') !== false ||
                                   strpos($fullOutput, 'updating') !== false ||
                                   strpos($fullOutput, 'installing') !== false ||
                                   strpos($fullOutput, 'removals') !== false ||
                                   $exitCode === 0;
                    
                    if (!$isSuccessful && $exitCode !== 0) {
                        // Действительная ошибка
                        $this->error('❌ Ошибка при обновлении Composer зависимостей');
                        if (!empty($errorOutput)) {
                            $this->error($errorOutput);
                        }
                        if (!empty($output)) {
                            $this->line($output);
                        }
                        $this->warn('');
                        $this->warn('Попробуйте выполнить вручную:');
                        $this->line("   {$composerCommand} install --no-dev --optimize-autoloader");
                        return Command::FAILURE;
                    }
                    
                    // Если есть предупреждения, но не ошибки
                    if (strpos($fullOutput, 'Warning:') !== false && !$hasRealError) {
                        $this->warn('⚠️  Composer выполнен с предупреждениями (но без ошибок)');
                    }
                } catch (\Exception $e) {
                    $this->error('❌ Ошибка при обновлении Composer зависимостей: ' . $e->getMessage());
                    $this->warn('');
                    $this->warn('Попробуйте выполнить вручную:');
                    $this->line("   {$composerCommand} install --no-dev --optimize-autoloader");
                    return Command::FAILURE;
                }
                
                $this->info('✅ Composer зависимости обновлены');
            }
            $bar->advance();

            // 3. NPM install
            if (file_exists(base_path('package.json'))) {
                $this->newLine();
                $this->info('📦 Обновление NPM зависимостей...');
                
                // Загружаем nvm если доступен
                $nvmCommand = $this->getNvmCommand();
                $npmCommand = $nvmCommand ? "{$nvmCommand} && npm" : 'npm';
                
                try {
                    if ($nvmCommand) {
                        $result = Process::run("{$nvmCommand} && npm install");
                    } else {
                        $result = Process::run('npm install');
                    }
                    
                    if (!$result->successful()) {
                        $this->error('❌ Ошибка при обновлении NPM зависимостей');
                        $this->error($result->errorOutput());
                        return Command::FAILURE;
                    }
                } catch (\Exception $e) {
                    $command = $nvmCommand 
                        ? ['bash', '-c', "{$nvmCommand} && npm install"]
                        : ['npm', 'install'];
                    
                    $process = new SymfonyProcess($command);
                    $process->run();
                    
                    if (!$process->isSuccessful()) {
                        $this->error('❌ Ошибка при обновлении NPM зависимостей');
                        $this->error($process->getErrorOutput());
                        return Command::FAILURE;
                    }
                }
                
                $this->info('✅ NPM зависимости обновлены');
            }
            $bar->advance();

            // 4. Build frontend
            if (!$this->option('skip-build') && file_exists(base_path('package.json'))) {
                $this->newLine();
                $this->info('🔨 Сборка фронтенда...');
                
                // Загружаем nvm если доступен
                $nvmCommand = $this->getNvmCommand();
                
                $buildOutput = '';
                $buildError = '';
                
                try {
                    if ($nvmCommand) {
                        $result = Process::run("{$nvmCommand} && npm run build");
                    } else {
                        $result = Process::run('npm run build');
                    }
                    
                    $buildOutput = $result->output();
                    $buildError = $result->errorOutput();
                    
                    if (!$result->successful()) {
                        $this->error('❌ Ошибка при сборке фронтенда');
                        $this->error($buildError ?: $buildOutput);
                        $this->warn('');
                        $this->warn('Попробуйте выполнить вручную:');
                        $this->line('   npm run build');
                        return Command::FAILURE;
                    }
                } catch (\Exception $e) {
                    $command = $nvmCommand 
                        ? ['bash', '-c', "{$nvmCommand} && npm run build"]
                        : ['npm', 'run', 'build'];
                    
                    $process = new SymfonyProcess($command);
                    $process->setTimeout(600); // 10 минут для сборки
                    $process->run();
                    
                    $buildOutput = $process->getOutput();
                    $buildError = $process->getErrorOutput();
                    
                    if (!$process->isSuccessful()) {
                        $this->error('❌ Ошибка при сборке фронтенда');
                        $this->error($buildError ?: $buildOutput);
                        $this->warn('');
                        $this->warn('Попробуйте выполнить вручную:');
                        $this->line('   npm run build');
                        return Command::FAILURE;
                    }
                }
                
                // Проверяем, что файлы сборки действительно созданы
                $buildDir = base_path('public/build');
                $manifestFile = $buildDir . '/.vite/manifest.json';
                
                if (file_exists($manifestFile) || is_dir($buildDir)) {
                    $this->info('✅ Фронтенд собран успешно');
                    $this->line('   Файлы сборки находятся в: public/build');
                } else {
                    $this->warn('⚠️  Сборка выполнена, но файлы не найдены в public/build');
                    $this->warn('   Проверьте вывод сборки выше');
                }
            } elseif ($this->option('skip-build')) {
                $this->warn('⚠️  Сборка фронтенда пропущена (--skip-build)');
                $this->warn('   Убедитесь, что фронтенд собран вручную!');
            }
            $bar->advance();

            // 5. Migrations
            if (!$this->option('skip-migrations')) {
                $this->newLine();
                $this->info('🗄️  Выполнение миграций...');
                Artisan::call('migrate', ['--force' => true]);
                $this->info('✅ Миграции выполнены');
            }
            $bar->advance();

            // 6. Clear cache
            $this->newLine();
            $this->info('🧹 Очистка кэша...');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Очищаем кеш Vite/фронтенда если нужно
            $viteCacheDir = base_path('public/build/.vite');
            if (is_dir($viteCacheDir)) {
                // Кеш Vite очищается автоматически при сборке
            }
            
            $this->info('✅ Кэш очищен');
            $this->warn('💡 Если изменения не видны, очистите кеш браузера (Ctrl+F5 или Cmd+Shift+R)');
            $bar->advance();

            // 7. Optimize
            if (!$this->option('skip-optimize')) {
                $this->newLine();
                $this->info('⚡ Оптимизация приложения...');
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');
                $this->info('✅ Приложение оптимизировано');
            }
            $bar->advance();

            $bar->finish();
            $this->newLine(2);
            $this->info('🎉 Обновление проекта завершено успешно!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error('❌ Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Определить версию PHP для использования
     */
    protected function detectPhpVersion(): ?string
    {
        // Проверяем текущую версию PHP
        $currentPhp = PHP_VERSION;
        if (version_compare($currentPhp, '8.2', '>=')) {
            return null; // Используем текущий PHP
        }
        
        // Если текущая версия меньше 8.2, ищем php8.2
        $phpVersions = ['php8.2', 'php82', '/usr/bin/php8.2', '/usr/local/bin/php8.2'];
        
        foreach ($phpVersions as $phpVersion) {
            try {
                // Проверяем через which или напрямую
                if (strpos($phpVersion, '/') === 0) {
                    // Это полный путь
                    if (file_exists($phpVersion) && is_executable($phpVersion)) {
                        return $phpVersion;
                    }
                } else {
                    // Это команда, проверяем через which
                    $process = new SymfonyProcess(['which', $phpVersion]);
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        $path = trim($process->getOutput());
                        if (!empty($path)) {
                            return $phpVersion;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Если не нашли, возвращаем null (будет использован системный PHP)
        return null;
    }

    /**
     * Определить путь к composer
     */
    protected function detectComposerPath(): ?string
    {
        // Стандартные пути к composer
        $composerPaths = [
            '/home/d/dsc23ytp/.local/bin/composer',
            '~/.local/bin/composer',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];
        
        foreach ($composerPaths as $path) {
            // Заменяем ~ на домашнюю директорию
            if (strpos($path, '~') === 0) {
                $path = str_replace('~', getenv('HOME') ?: getenv('USERPROFILE') ?: '/home/' . get_current_user(), $path);
            }
            
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Попробуем найти через which
        try {
            $process = new SymfonyProcess(['which', 'composer']);
            $process->run();
            
            if ($process->isSuccessful()) {
                $path = trim($process->getOutput());
                if (!empty($path) && file_exists($path)) {
                    return $path;
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибку
        }
        
        return null;
    }

    /**
     * Получить команду для загрузки nvm
     */
    protected function getNvmCommand(): ?string
    {
        $nvmDir = getenv('NVM_DIR') ?: (getenv('HOME') . '/.nvm');
        
        if (file_exists($nvmDir . '/nvm.sh')) {
            return "export NVM_DIR=\"{$nvmDir}\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && \. \"\$NVM_DIR/nvm.sh\" && nvm use default";
        }
        
        return null;
    }

    /**
     * Найти php8.2
     */
    protected function findPhp82(): ?string
    {
        $phpVersions = ['php8.2', 'php82', '/usr/bin/php8.2', '/usr/local/bin/php8.2'];
        
        foreach ($phpVersions as $phpVersion) {
            try {
                if (strpos($phpVersion, '/') === 0) {
                    if (file_exists($phpVersion) && is_executable($phpVersion)) {
                        return $phpVersion;
                    }
                } else {
                    $process = new SymfonyProcess(['which', $phpVersion]);
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        $path = trim($process->getOutput());
                        if (!empty($path)) {
                            return $phpVersion;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return null;
    }
}

