<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

/**
 * Команда деплоя проекта
 * 
 * Выполняет сборку фронтенда, коммит в git и отправку запроса на обновление кода на сервере.
 * 
 * @package App\Console\Commands
 */
class Deploy extends Command
{
    /**
     * Имя и сигнатура консольной команды
     *
     * @var string
     */
    protected $signature = 'deploy 
                            {--message= : Кастомное сообщение для коммита}
                            {--skip-build : Пропустить npm run build}
                            {--dry-run : Показать что будет сделано без выполнения}
                            {--insecure : Отключить проверку SSL сертификата (для разработки)}
                            {--with-seed : Выполнить seeders на сервере (по умолчанию пропускаются)}
                            {--force : Принудительная отправка (force push) - перезаписывает удаленную ветку}
                            {--npm-path= : Путь к npm (переопределяет автоматическое определение)}
                            {--composer-path= : Путь к composer (переопределяет автоматическое определение)}';

    /**
     * Описание консольной команды
     *
     * @var string
     */
    protected $description = 'Деплой проекта: сборка, коммит в git, отправка на сервер';

    /**
     * URL git репозитория
     * 
     * @var string|null
     */
    protected $gitRepository = null;

    /**
     * Выполнение консольной команды
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 Начало процесса деплоя...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        try {
            // Шаг 1: Сборка фронтенда
            if (!$this->option('skip-build')) {
                $this->buildFrontend($dryRun);
            } else {
                $this->warn('⚠️  Пропущена сборка фронтенда (--skip-build)');
            }

            // Шаг 2: Проверка git статуса
            $hasChanges = $this->checkGitStatus($dryRun);
            
            if (!$hasChanges && !$dryRun) {
                $this->warn('⚠️  Нет изменений для коммита.');
                // В неинтерактивном режиме автоматически продолжаем
                if (php_sapi_name() === 'cli' && !$this->option('no-interaction')) {
                    if (!$this->confirm('Продолжить деплой без изменений?', false)) {
                        $this->info('Деплой отменен.');
                        return Command::FAILURE;
                    }
                } else {
                    $this->info('  ℹ️  Продолжаем деплой без изменений (неинтерактивный режим)');
                }
            }

            // Шаг 3: Проверка remote репозитория
            $this->ensureGitRemote($dryRun);

            // Шаг 4: Добавление изменений в git
            if ($hasChanges) {
                $this->addChangesToGit($dryRun);
                
                // Шаг 5: Создание коммита
                $commitMessage = $this->createCommit($dryRun);
                
                // Шаг 6: Отправка в репозиторий
                $this->pushToRepository($dryRun);
            }

            // Шаг 7: Отправка POST запроса на сервер
            if (!$dryRun) {
                $this->sendDeployRequest();
            } else {
                $this->info('📤 [DRY-RUN] Отправка POST запроса на сервер пропущена');
            }

            $this->newLine();
            $this->info('✅ Деплой успешно завершен!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка деплоя: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Получить URL git репозитория из конфигурации или .env
     *
     * @return string
     */
    protected function getGitRepository(): string
    {
        if ($this->gitRepository === null) {
            // Пытаемся получить из .env
            $this->gitRepository = env('GIT_REPOSITORY_URL');
            
            // Если не указан, пытаемся получить из текущего remote
            if (!$this->gitRepository) {
                $process = Process::run('git remote get-url origin');
                if ($process->successful()) {
                    $this->gitRepository = trim($process->output());
                } else {
                    throw new \Exception('Не указан GIT_REPOSITORY_URL в .env и не найден git remote origin');
                }
            }
        }
        
        return $this->gitRepository;
    }

    /**
     * Получить путь к npm
     * 
     * Определяет путь к npm в следующем порядке:
     * 1. Из опции команды --npm-path
     * 2. Из переменной окружения NPM_PATH
     * 3. Через which npm (если в PATH)
     * 4. Через nvm (поиск в ~/.nvm)
     * 5. Стандартный путь npm
     *
     * @return string Путь к npm
     */
    protected function getNpmPath(): string
    {
        // 1. Проверяем опцию команды
        $npmPath = $this->option('npm-path');
        if ($npmPath && file_exists($npmPath)) {
            return $npmPath;
        }

        // 2. Проверяем переменную окружения
        $npmPath = env('NPM_PATH');
        if ($npmPath && file_exists($npmPath)) {
            return $npmPath;
        }

        // 2. Пытаемся найти через which (если в PATH)
        $process = Process::run('which npm');
        if ($process->successful()) {
            $path = trim($process->output());
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        // 3. Ищем через nvm в домашней директории
        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '~');
        $nvmPath = $homeDir . '/.nvm';
        
        if (is_dir($nvmPath)) {
            // Ищем последнюю установленную версию Node
            $versionsPath = $nvmPath . '/versions/node';
            if (is_dir($versionsPath)) {
                $versions = glob($versionsPath . '/*', GLOB_ONLYDIR);
                if (!empty($versions)) {
                    // Сортируем по версии (последняя версия)
                    usort($versions, function($a, $b) {
                        return version_compare(basename($b), basename($a));
                    });
                    
                    $latestVersion = $versions[0];
                    $npmPath = $latestVersion . '/bin/npm';
                    
                    if (file_exists($npmPath)) {
                        return $npmPath;
                    }
                }
            }
        }

        // 4. Стандартный путь
        return 'npm';
    }

    /**
     * Получить путь к composer
     * 
     * Определяет путь к composer в следующем порядке:
     * 1. Из опции команды --composer-path
     * 2. Из переменной окружения COMPOSER_PATH
     * 3. Через which composer (если в PATH)
     * 4. Через ~/bin/composer (пользовательский путь)
     * 5. Стандартный путь composer
     *
     * @return string Путь к composer
     */
    protected function getComposerPath(): string
    {
        // 1. Проверяем опцию команды
        $composerPath = $this->option('composer-path');
        if ($composerPath && file_exists($composerPath)) {
            return $composerPath;
        }

        // 2. Проверяем переменную окружения
        $composerPath = env('COMPOSER_PATH');
        if ($composerPath && file_exists($composerPath)) {
            return $composerPath;
        }

        // 2. Пытаемся найти через which (если в PATH)
        $process = Process::run('which composer');
        if ($process->successful()) {
            $path = trim($process->output());
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        // 3. Проверяем пользовательский путь ~/bin/composer
        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '~');
        $userComposerPath = $homeDir . '/bin/composer';
        
        if (file_exists($userComposerPath)) {
            return $userComposerPath;
        }

        // 4. Стандартный путь
        return 'composer';
    }

    /**
     * Сборка фронтенда
     * 
     * Выполняет сборку React приложения из папки frontend
     * Использует автоматически определенные пути к npm
     *
     * @param bool $dryRun Режим проверки без выполнения
     * @return void
     * @throws \Exception
     */
    protected function buildFrontend(bool $dryRun): void
    {
        $this->info('📦 Шаг 1: Сборка фронтенда...');
        
        // Определяем путь к npm
        $npmPath = $this->getNpmPath();
        
        if ($dryRun) {
            $this->line("  [DRY-RUN] Выполнение: cd frontend && {$npmPath} run build");
            $this->line("  [DRY-RUN] npm найден: {$npmPath}");
            return;
        }

        // Проверяем наличие package.json в frontend
        $frontendPath = base_path('frontend');
        $packageJsonPath = $frontendPath . '/package.json';
        
        if (!File::exists($packageJsonPath)) {
            $this->warn('  ⚠️  package.json не найден в frontend/');
            $this->warn('  💡 Пропускаем сборку. Создайте React приложение в frontend/');
            $this->newLine();
            return;
        }

        // Выводим информацию о используемом npm
        $this->line("  🔧 Используется npm: {$npmPath}");
        
        // Проверяем доступность npm
        $checkProcess = Process::run("{$npmPath} --version");
        if (!$checkProcess->successful()) {
            throw new \Exception(
                "npm недоступен по пути: {$npmPath}\n" .
                "Установите npm или укажите путь в переменной NPM_PATH в .env"
            );
        }
        
        $npmVersion = trim($checkProcess->output());
        $this->line("  📦 Версия npm: {$npmVersion}");

        // Выполняем сборку в папке frontend
        // Используем полный путь к npm для надежности
        $process = Process::path($frontendPath)
            ->env([
                'PATH' => getenv('PATH') . ':' . dirname($npmPath),
                'NVM_DIR' => getenv('NVM_DIR') ?: (getenv('HOME') . '/.nvm'),
            ])
            ->run("{$npmPath} run build");

        if (!$process->successful()) {
            $errorOutput = $process->errorOutput();
            throw new \Exception("Ошибка сборки фронтенда:\n{$errorOutput}\n\n" .
                "Проверьте:\n" .
                "1. Установлен ли npm: {$npmPath}\n" .
                "2. Выполнены ли зависимости: cd frontend && {$npmPath} install\n" .
                "3. Правильность конфигурации Vite");
        }

        // Проверяем наличие собранных файлов
        $distPath = $frontendPath . '/dist';
        $indexHtmlPath = $distPath . '/index.html';

        if (!File::exists($indexHtmlPath)) {
            throw new \Exception(
                "Файл {$indexHtmlPath} не найден после сборки.\n" .
                "Проверьте конфигурацию Vite и путь сборки в vite.config.js"
            );
        }

        $this->info('  ✅ Сборка завершена успешно');
        $this->info("  📁 Собранные файлы: {$distPath}");
        $this->newLine();
    }

    /**
     * Проверка git статуса
     * 
     * Проверяет наличие изменений и предупреждает о больших файлах
     *
     * @param bool $dryRun Режим проверки без выполнения
     * @return bool Есть ли изменения
     * @throws \Exception
     */
    protected function checkGitStatus(bool $dryRun): bool
    {
        $this->info('📋 Шаг 2: Проверка статуса git...');
        
        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git status');
            return true;
        }

        $process = Process::run('git status --porcelain');
        
        if (!$process->successful()) {
            throw new \Exception("Ошибка проверки git статуса:\n" . $process->errorOutput());
        }

        $output = trim($process->output());
        $hasChanges = !empty($output);

        if ($hasChanges) {
            $this->line('  📝 Найдены изменения:');
            $this->line($output);
            
            // Проверяем на большие файлы
            $files = explode("\n", $output);
            $largeFiles = [];
            foreach ($files as $file) {
                $file = trim($file);
                if (empty($file)) continue;
                
                // Извлекаем имя файла (убираем статус M, A, ?? и т.д.)
                $fileName = preg_replace('/^[MADRC\?\s!]+/', '', $file);
                $fileName = trim($fileName);
                
                // Проверяем расширения больших файлов
                if (preg_match('/\.(rar|zip|7z|tar\.gz|tar)$/i', $fileName)) {
                    $largeFiles[] = $fileName;
                } elseif (file_exists($fileName)) {
                    $size = filesize($fileName);
                    // Предупреждаем о файлах больше 10MB
                    if ($size > 10 * 1024 * 1024) {
                        $sizeMB = round($size / 1024 / 1024, 2);
                        $largeFiles[] = "{$fileName} ({$sizeMB} MB)";
                    }
                }
            }
            
            if (!empty($largeFiles)) {
                $this->newLine();
                $this->warn('  ⚠️  Обнаружены большие файлы:');
                foreach ($largeFiles as $file) {
                    $this->warn("     - {$file}");
                }
                $this->warn('  💡 Рекомендуется добавить их в .gitignore перед коммитом');
                // В неинтерактивном режиме пропускаем подтверждение
                if (php_sapi_name() === 'cli' && !$this->option('no-interaction')) {
                    if (!$this->confirm('  Продолжить с этими файлами?', false)) {
                        throw new \Exception('Операция отменена. Добавьте большие файлы в .gitignore.');
                    }
                } else {
                    $this->info('  ℹ️  Продолжаем с большими файлами (неинтерактивный режим)');
                }
            }
        } else {
            $this->line('  ℹ️  Изменений не обнаружено');
        }

        $this->newLine();
        return $hasChanges;
    }

    /**
     * Проверка и настройка git remote
     * 
     * Убеждается, что remote origin настроен правильно
     *
     * @param bool $dryRun Режим проверки без выполнения
     * @return void
     * @throws \Exception
     */
    protected function ensureGitRemote(bool $dryRun): void
    {
        $this->info('🔗 Шаг 3: Проверка git remote...');
        
        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git remote -v');
            return;
        }

        $process = Process::run('git remote -v');
        
        if (!$process->successful()) {
            throw new \Exception("Ошибка проверки git remote:\n" . $process->errorOutput());
        }

        $output = trim($process->output());
        $gitRepository = $this->getGitRepository();
        
        // Проверяем, существует ли origin с правильным URL
        if (empty($output)) {
            $this->line('  ➕ Добавление origin remote...');
            $process = Process::run("git remote add origin {$gitRepository}");
            
            if (!$process->successful()) {
                throw new \Exception("Ошибка добавления remote:\n" . $process->errorOutput());
            }
            
            $this->info('  ✅ Remote origin добавлен');
        } else {
            // Проверяем, правильный ли URL у origin
            if (!str_contains($output, $gitRepository)) {
                $this->line('  🔄 Обновление origin remote...');
                $process = Process::run("git remote set-url origin {$gitRepository}");
                
                if (!$process->successful()) {
                    throw new \Exception("Ошибка обновления remote:\n" . $process->errorOutput());
                }
                
                $this->info('  ✅ Remote origin обновлен');
            } else {
                $this->line('  ✅ Remote origin настроен правильно');
            }
        }

        $this->newLine();
    }

    /**
     * Добавление изменений в git
     * 
     * Добавляет все изменения, включая собранные файлы из frontend/dist
     *
     * @param bool $dryRun Режим проверки без выполнения
     * @return void
     * @throws \Exception
     */
    protected function addChangesToGit(bool $dryRun): void
    {
        $this->info('➕ Шаг 4: Добавление изменений в git...');
        
        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git add .');
            return;
        }

        // Принудительно добавляем собранные файлы из frontend/dist (на случай если они были в .gitignore)
        $distPath = base_path('frontend/dist');
        if (File::exists($distPath)) {
            $process = Process::run('git add -f frontend/dist');
            if (!$process->successful()) {
                $this->warn('  ⚠️  Предупреждение: не удалось добавить frontend/dist');
            } else {
                $this->line('  ✅ Добавлен frontend/dist (React приложение)');
            }
        }

        // Затем добавляем все остальные изменения
        $process = Process::run('git add .');
        
        if (!$process->successful()) {
            throw new \Exception("Ошибка добавления файлов в git:\n" . $process->errorOutput());
        }

        $this->info('  ✅ Файлы добавлены в git');
        $this->newLine();
    }

    /**
     * Создание коммита
     * 
     * Создает коммит с указанным или автоматическим сообщением
     *
     * @param bool $dryRun Режим проверки без выполнения
     * @return string Сообщение коммита
     * @throws \Exception
     */
    protected function createCommit(bool $dryRun): string
    {
        $this->info('💾 Шаг 5: Создание коммита...');
        
        $customMessage = $this->option('message');
        $commitMessage = $customMessage ?: 'Deploy: ' . now()->format('Y-m-d H:i:s');
        
        if ($dryRun) {
            $this->line("  [DRY-RUN] Выполнение: git commit -m \"{$commitMessage}\"");
            return $commitMessage;
        }

        $process = Process::run(['git', 'commit', '-m', $commitMessage]);

        if (!$process->successful()) {
            // Возможно, коммит уже существует или нет изменений
            $errorOutput = $process->errorOutput();
            if (str_contains($errorOutput, 'nothing to commit')) {
                $this->warn('  ⚠️  Нет изменений для коммита');
                return $commitMessage;
            }
            throw new \Exception("Ошибка создания коммита:\n" . $errorOutput);
        }

        $this->info("  ✅ Коммит создан: {$commitMessage}");
        $this->newLine();
        return $commitMessage;
    }

    /**
     * Отправка в репозиторий
     * 
     * Отправляет изменения в удаленный репозиторий
     *
     * @param bool $dryRun Режим проверки без выполнения
     * @return void
     * @throws \Exception
     */
    protected function pushToRepository(bool $dryRun): void
    {
        $this->info('📤 Шаг 6: Отправка в репозиторий...');
        
        // Определяем текущую ветку
        $branchProcess = Process::run('git rev-parse --abbrev-ref HEAD');
        $branch = trim($branchProcess->output()) ?: 'main';
        
        $forcePush = $this->option('force');
        
        if ($forcePush) {
            $this->warn('  ⚠️  ВНИМАНИЕ: Используется принудительная отправка (--force)');
            $this->warn('  ⚠️  Это перезапишет удаленную ветку и может удалить коммиты!');
        }
        
        if ($dryRun) {
            $pushCommand = $forcePush ? "git push --force origin {$branch}" : "git push origin {$branch}";
            $this->line("  [DRY-RUN] Выполнение: {$pushCommand}");
            return;
        }

        // Увеличиваем таймаут для git push (большие файлы могут требовать больше времени)
        $pushCommand = $forcePush ? "git push --force origin {$branch}" : "git push origin {$branch}";
        $process = Process::timeout(300) // 5 минут
            ->run($pushCommand);

        if (!$process->successful()) {
            $errorOutput = $process->errorOutput();
            
            // Проверяем, нужно ли установить upstream
            if (str_contains($errorOutput, 'no upstream branch')) {
                $this->line("  🔄 Установка upstream для ветки {$branch}...");
                $upstreamCommand = $forcePush ? "git push --force -u origin {$branch}" : "git push -u origin {$branch}";
                $process = Process::timeout(300)
                    ->run($upstreamCommand);
                
                if (!$process->successful()) {
                    throw new \Exception("Ошибка отправки в репозиторий:\n" . $process->errorOutput());
                }
            } else {
                // Проверяем на таймаут
                if (str_contains($errorOutput, 'timeout') || str_contains($errorOutput, 'exceeded')) {
                    throw new \Exception(
                        "Таймаут отправки в репозиторий. Возможно, файлы слишком большие.\n" .
                        "Проверьте, нет ли в коммите больших файлов (архивы, изображения и т.д.).\n" .
                        "Рекомендуется добавить их в .gitignore."
                    );
                }
                
                // Если обычный push не прошел из-за non-fast-forward, предлагаем force
                if (str_contains($errorOutput, 'non-fast-forward') && !$forcePush) {
                    throw new \Exception(
                        "Ошибка отправки в репозиторий: локальная ветка отстает от удаленной.\n" .
                        "Если вы делаете откат, используйте флаг --force:\n" .
                        "php artisan deploy --force --insecure\n" .
                        "⚠️  ВНИМАНИЕ: --force перезапишет удаленную ветку!"
                    );
                }
                
                throw new \Exception("Ошибка отправки в репозиторий:\n" . $errorOutput);
            }
        }

        $this->info("  ✅ Изменения отправлены в ветку: {$branch}" . ($forcePush ? " (force push)" : ""));
        $this->newLine();
    }

    /**
     * Отправка POST запроса на сервер
     * 
     * Отправляет запрос на сервер для автоматического обновления кода из git
     *
     * @return void
     * @throws \Exception
     */
    protected function sendDeployRequest(): void
    {
        $this->info('🌐 Шаг 7: Отправка запроса на сервер...');
        
        $serverUrl = env('DEPLOY_SERVER_URL');
        $deployToken = env('DEPLOY_TOKEN');

        if (!$serverUrl) {
            $this->warn('  ⚠️  DEPLOY_SERVER_URL не настроен в .env - пропуск отправки на сервер');
            $this->line('  💡 Добавьте DEPLOY_SERVER_URL и DEPLOY_TOKEN в .env для автоматического деплоя');
            $this->newLine();
            return;
        }

        if (!$deployToken) {
            $this->warn('  ⚠️  DEPLOY_TOKEN не настроен в .env - пропуск отправки на сервер');
            $this->line('  💡 Добавьте DEPLOY_TOKEN в .env для автоматического деплоя');
            $this->newLine();
            return;
        }

        // Получаем текущий commit hash
        $commitProcess = Process::run('git rev-parse HEAD');
        $commitHash = trim($commitProcess->output()) ?: 'unknown';

        // Формируем правильный URL
        $deployUrl = rtrim($serverUrl, '/');
        
        // Убираем /api/deploy если он уже есть в URL
        if (str_contains($deployUrl, '/api/deploy')) {
            $pos = strpos($deployUrl, '/api/deploy');
            $deployUrl = substr($deployUrl, 0, $pos);
            $deployUrl = rtrim($deployUrl, '/');
        }
        
        // Добавляем /api/deploy
        $deployUrl .= '/api/deploy';

        $this->line("  📡 URL: {$deployUrl}");
        $this->line("  🔑 Commit: " . substr($commitHash, 0, 7));
        $this->line("  🔐 Token: " . (substr($deployToken, 0, 3) . '...' . substr($deployToken, -3)));

        try {
            $httpClient = Http::timeout(300); // 5 минут таймаут

            // Отключить проверку SSL для локальной разработки (если указана опция)
            if ($this->option('insecure') || env('APP_ENV') === 'local') {
                $httpClient = $httpClient->withoutVerifying();
                if ($this->option('insecure')) {
                    $this->warn('  ⚠️  Проверка SSL сертификата отключена (--insecure)');
                } else {
                    $this->line('  ℹ️  Проверка SSL отключена (локальное окружение)');
                }
            }

            // Дополнительные настройки для cURL при проблемах с SSL
            $curlOptions = [];
            if ($this->option('insecure')) {
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
            }
            
            // Пробуем разные версии TLS
            $curlOptions[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
            
            // Увеличиваем таймауты
            $curlOptions[CURLOPT_CONNECTTIMEOUT] = 30;
            $curlOptions[CURLOPT_TIMEOUT] = 300;
            
            // Разрешаем редиректы
            $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
            $curlOptions[CURLOPT_MAXREDIRS] = 5;

            $response = $httpClient->withOptions($curlOptions)
                ->withHeaders([
                    'X-Deploy-Token' => $deployToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-React-Deploy/1.0',
                ])
                ->post($deployUrl, [
                    'commit_hash' => $commitHash,
                    'repository' => $this->getGitRepository(),
                    'branch' => trim(Process::run('git rev-parse --abbrev-ref HEAD')->output() ?: 'main'),
                    'deployed_by' => get_current_user(),
                    'timestamp' => now()->toDateTimeString(),
                    'run_seeders' => $this->option('with-seed'),
                ]);

            // Проверяем статус ответа
            if ($response->successful()) {
                $data = $response->json();
                
                $this->newLine();
                $this->info('  ✅ Сервер ответил успешно:');
                
                if (isset($data['data'])) {
                    $dataArray = $data['data'];
                    
                    if (isset($dataArray['php_path'])) {
                        $this->line("     PHP: {$dataArray['php_path']} (v{$dataArray['php_version']})");
                    }
                    
                    if (isset($dataArray['git_pull'])) {
                        $this->line("     Git Pull: {$dataArray['git_pull']}");
                    }
                    
                    if (isset($dataArray['composer_install'])) {
                        $this->line("     Composer: {$dataArray['composer_install']}");
                    }
                    
                    if (isset($dataArray['migrations'])) {
                        $migrations = $dataArray['migrations'];
                        if (is_array($migrations) && isset($migrations['status'])) {
                            if ($migrations['status'] === 'success') {
                                $this->line("     Миграции: " . ($migrations['message'] ?? 'успешно'));
                            } else {
                                $this->warn("     Миграции: ошибка - " . ($migrations['error'] ?? 'неизвестная ошибка'));
                            }
                        }
                    }
                    
                    if (isset($dataArray['seeders'])) {
                        $seeders = $dataArray['seeders'];
                        if (is_array($seeders) && isset($seeders['status'])) {
                            if ($seeders['status'] === 'skipped') {
                                $this->line("     Seeders: " . ($seeders['message'] ?? 'пропущены'));
                            } elseif ($seeders['status'] === 'success') {
                                $this->line("     Seeders: " . ($seeders['message'] ?? 'успешно'));
                            } elseif ($seeders['status'] === 'partial') {
                                $this->warn("     Seeders: " . ($seeders['message'] ?? 'частично выполнены'));
                            } else {
                                $this->warn("     Seeders: ошибка - " . ($seeders['error'] ?? 'неизвестная ошибка'));
                            }
                        }
                    }
                    
                    if (isset($dataArray['duration_seconds'])) {
                        $this->line("     Время выполнения: {$dataArray['duration_seconds']}с");
                    }
                    
                    if (isset($dataArray['deployed_at'])) {
                        $this->line("     Дата: {$dataArray['deployed_at']}");
                    }
                } else {
                    $this->line("     Ответ: " . json_encode($data, JSON_UNESCAPED_UNICODE));
                }
            } else {
                $errorData = $response->json();
                throw new \Exception(
                    "Ошибка деплоя на сервере (HTTP {$response->status()}): " . 
                    ($errorData['message'] ?? $response->body())
                );
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMessage = $e->getMessage();
            
            // Детальная диагностика ошибки
            $this->newLine();
            $this->error('❌ Ошибка подключения к серверу');
            $this->line("  📡 URL: {$deployUrl}");
            $this->line("  🔍 Ошибка: {$errorMessage}");
            
            // Проверяем тип ошибки и даем рекомендации
            if (str_contains($errorMessage, 'Connection was reset') || str_contains($errorMessage, 'cURL error 35')) {
                $this->newLine();
                $this->warn('  💡 Возможные причины:');
                $this->line('     1. Проблема с SSL/TLS сертификатом на сервере');
                $this->line('     2. Несовместимость версий TLS между клиентом и сервером');
                $this->line('     3. Файрвол или прокси блокирует соединение');
                $this->line('     4. Сервер недоступен или перегружен');
                $this->newLine();
                $this->line('  🔧 Рекомендации:');
                $this->line('     - Проверьте доступность сервера: curl -I ' . $deployUrl);
                $this->line('     - Проверьте SSL сертификат: openssl s_client -connect ' . parse_url($deployUrl, PHP_URL_HOST) . ':443');
                $this->line('     - Попробуйте использовать HTTP вместо HTTPS (только для тестирования)');
                $this->line('     - Проверьте настройки файрвола на сервере');
            } elseif (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'timed out')) {
                $this->newLine();
                $this->warn('  💡 Возможные причины:');
                $this->line('     1. Сервер не отвечает в течение 5 минут');
                $this->line('     2. Медленное интернет-соединение');
                $this->line('     3. Сервер перегружен');
            } elseif (str_contains($errorMessage, 'SSL') || str_contains($errorMessage, 'certificate')) {
                $this->newLine();
                $this->warn('  💡 Проблема с SSL сертификатом');
                $this->line('     Попробуйте использовать флаг --insecure (уже использован)');
                $this->line('     Или проверьте валидность SSL сертификата на сервере');
            }
            
            throw new \Exception("Не удалось подключиться к серверу: {$errorMessage}");
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка отправки запроса');
            $this->line("  🔍 Детали: " . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line("  📋 Trace: " . $e->getTraceAsString());
            }
            
            throw new \Exception("Ошибка отправки запроса: " . $e->getMessage());
        }

        $this->newLine();
    }
}

