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
                
                try {
                    $result = Process::run('composer install --no-dev --optimize-autoloader');
                    
                    if (!$result->successful()) {
                        $this->error('❌ Ошибка при обновлении Composer зависимостей');
                        $this->error($result->errorOutput());
                        return Command::FAILURE;
                    }
                } catch (\Exception $e) {
                    $process = new SymfonyProcess(['composer', 'install', '--no-dev', '--optimize-autoloader']);
                    $process->run();
                    
                    if (!$process->isSuccessful()) {
                        $this->error('❌ Ошибка при обновлении Composer зависимостей');
                        $this->error($process->getErrorOutput());
                        return Command::FAILURE;
                    }
                }
                
                $this->info('✅ Composer зависимости обновлены');
            }
            $bar->advance();

            // 3. NPM install
            if (file_exists(base_path('package.json'))) {
                $this->newLine();
                $this->info('📦 Обновление NPM зависимостей...');
                
                try {
                    $result = Process::run('npm install --production');
                    
                    if (!$result->successful()) {
                        $this->error('❌ Ошибка при обновлении NPM зависимостей');
                        $this->error($result->errorOutput());
                        return Command::FAILURE;
                    }
                } catch (\Exception $e) {
                    $process = new SymfonyProcess(['npm', 'install', '--production']);
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
                
                try {
                    $result = Process::run('npm run build');
                    
                    if (!$result->successful()) {
                        $this->error('❌ Ошибка при сборке фронтенда');
                        $this->error($result->errorOutput());
                        return Command::FAILURE;
                    }
                } catch (\Exception $e) {
                    $process = new SymfonyProcess(['npm', 'run', 'build']);
                    $process->run();
                    
                    if (!$process->isSuccessful()) {
                        $this->error('❌ Ошибка при сборке фронтенда');
                        $this->error($process->getErrorOutput());
                        return Command::FAILURE;
                    }
                }
                
                $this->info('✅ Фронтенд собран');
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
            $this->info('✅ Кэш очищен');
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
}

