<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для обработки запросов деплоя с сервера
 * 
 * Принимает POST запросы от команды deploy для автоматического обновления кода на сервере.
 * 
 * @package App\Http\Controllers\Api
 */
class DeployController extends Controller
{
    /**
     * Обработка запроса на деплой
     * 
     * Выполняет:
     * 1. Проверку токена авторизации
     * 2. Git pull из репозитория
     * 3. Composer install
     * 4. Миграции (если нужно)
     * 5. Seeders (если указано)
     * 6. Очистку кеша
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            // Проверка токена
            $token = $request->header('X-Deploy-Token');
            $expectedToken = env('DEPLOY_TOKEN');
            
            if (!$expectedToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'DEPLOY_TOKEN не настроен на сервере',
                ], 500);
            }
            
            if ($token !== $expectedToken) {
                Log::warning('Неверный токен деплоя', [
                    'ip' => $request->ip(),
                    'provided_token' => substr($token ?? '', 0, 3) . '...' . substr($token ?? '', -3),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный токен авторизации',
                ], 401);
            }
            
            // Получаем данные из запроса
            $commitHash = $request->input('commit_hash', 'unknown');
            $branch = $request->input('branch', 'main');
            $runSeeders = $request->input('run_seeders', false);
            
            // Проверяем и генерируем APP_KEY если отсутствует
            $appKey = env('APP_KEY');
            if (empty($appKey)) {
                $this->warn('APP_KEY отсутствует, генерируем...');
                Artisan::call('key:generate', ['--force' => true]);
                $this->info('APP_KEY сгенерирован');
            }
            
            $responseData = [
                'php_path' => Process::run('which php8.2')->output() ?: 'php',
                'php_version' => PHP_VERSION,
                'git_pull' => null,
                'composer_install' => null,
                'migrations' => null,
                'seeders' => null,
                'cache_clear' => null,
            ];
            
            // 1. Git pull
            $this->info('Выполнение git pull...');
            $gitPull = Process::timeout(60)->run('git pull origin ' . escapeshellarg($branch));
            
            if ($gitPull->successful()) {
                $responseData['git_pull'] = 'success';
                $this->info('Git pull выполнен успешно');
            } else {
                $responseData['git_pull'] = 'error: ' . $gitPull->errorOutput();
                $this->error('Ошибка git pull: ' . $gitPull->errorOutput());
            }
            
            // 2. Composer install
            $this->info('Выполнение composer install...');
            $composerPath = $this->getComposerPath();
            
            // Выводим информацию о используемом composer
            $this->info("Используется composer: {$composerPath}");
            
            // Проверяем существование файла если это полный путь
            if ($composerPath !== 'composer' && !file_exists($composerPath)) {
                $errorMsg = "Composer не найден по пути: {$composerPath}";
                $responseData['composer_install'] = 'error: ' . $errorMsg;
                $this->error($errorMsg);
                $this->warn('Проверьте путь к composer в переменной COMPOSER_PATH в .env');
                Log::error('[Deploy] Composer не найден', ['path' => $composerPath]);
            } else {
                // Проверяем доступность composer перед использованием
                $checkComposer = Process::run("{$composerPath} --version");
                if (!$checkComposer->successful()) {
                    $errorMsg = "Composer недоступен по пути: {$composerPath}";
                    $responseData['composer_install'] = 'error: ' . $errorMsg;
                    $this->error($errorMsg);
                    $this->warn('Проверьте путь к composer в переменной COMPOSER_PATH в .env');
                    Log::error('[Deploy] Composer недоступен', [
                        'path' => $composerPath,
                        'error' => $checkComposer->errorOutput(),
                    ]);
                } else {
                    $composerVersion = trim($checkComposer->output());
                    $this->info("Версия composer: {$composerVersion}");
                    
                    // Подготавливаем окружение для composer install
                    $env = [];
                    if ($composerPath !== 'composer') {
                        // Если используем полный путь, добавляем его директорию в PATH
                        $composerDir = dirname($composerPath);
                        $currentPath = getenv('PATH') ?: '';
                        $env['PATH'] = $composerDir . ':' . $currentPath;
                    }
                    
                    $composerInstall = Process::timeout(300)
                        ->path(base_path())
                        ->env($env)
                        ->run("{$composerPath} install --no-dev --optimize-autoloader --no-interaction");
                    
                    if ($composerInstall->successful()) {
                        $responseData['composer_install'] = 'success';
                        $this->info('Composer install выполнен успешно');
                    } else {
                        $errorOutput = $composerInstall->errorOutput();
                        $responseData['composer_install'] = 'error: ' . $errorOutput;
                        $this->error('Ошибка composer install: ' . $errorOutput);
                        Log::error('[Deploy] Ошибка composer install', [
                            'path' => $composerPath,
                            'error' => $errorOutput,
                        ]);
                    }
                }
            }
            }
            
            // 3. Миграции
            $this->info('Выполнение миграций...');
            $migrate = Artisan::call('migrate', ['--force' => true]);
            
            if ($migrate === 0) {
                $responseData['migrations'] = [
                    'status' => 'success',
                    'message' => 'Миграции выполнены успешно',
                ];
                $this->info('Миграции выполнены успешно');
            } else {
                $output = Artisan::output();
                $responseData['migrations'] = [
                    'status' => 'error',
                    'error' => $output ?: 'Неизвестная ошибка',
                ];
                $this->error('Ошибка миграций: ' . $output);
            }
            
            // 4. Seeders (если указано)
            if ($runSeeders) {
                $this->info('Выполнение seeders...');
                $seed = Artisan::call('db:seed', ['--force' => true]);
                
                if ($seed === 0) {
                    $responseData['seeders'] = [
                        'status' => 'success',
                        'message' => 'Seeders выполнены успешно',
                    ];
                    $this->info('Seeders выполнены успешно');
                } else {
                    $output = Artisan::output();
                    $responseData['seeders'] = [
                        'status' => 'error',
                        'error' => $output ?: 'Неизвестная ошибка',
                    ];
                    $this->warn('Ошибка seeders: ' . $output);
                }
            } else {
                $responseData['seeders'] = [
                    'status' => 'skipped',
                    'message' => 'Seeders пропущены (не указан флаг run_seeders)',
                ];
            }
            
            // 5. Очистка кеша
            $this->info('Очистка кеша...');
            try {
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                
                // Очистка cache может вызвать ошибку если таблица не существует
                // Выполняем с обработкой ошибок
                try {
                    Artisan::call('cache:clear');
                } catch (\Exception $e) {
                    // Если таблица cache не существует, это не критично
                    $this->warn('Ошибка очистки cache (возможно таблица не существует): ' . $e->getMessage());
                }
                
                $responseData['cache_clear'] = 'success';
                $this->info('Кеш очищен');
            } catch (\Exception $e) {
                $responseData['cache_clear'] = 'partial: ' . $e->getMessage();
                $this->warn('Частичная ошибка очистки кеша: ' . $e->getMessage());
            }
            
            // Время выполнения
            $duration = round(microtime(true) - $startTime, 2);
            $responseData['duration_seconds'] = $duration;
            $responseData['deployed_at'] = now()->toDateTimeString();
            
            Log::info('Деплой выполнен успешно', [
                'commit_hash' => $commitHash,
                'branch' => $branch,
                'duration' => $duration,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Деплой выполнен успешно',
                'data' => $responseData,
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Ошибка деплоя', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка выполнения деплоя: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Получить путь к composer
     * 
     * Определяет путь к composer аналогично команде Deploy
     * Использует те же методы определения пути
     *
     * @return string
     */
    protected function getComposerPath(): string
    {
        // 1. Читаем напрямую из .env (надежнее чем env() в рантайме)
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            if (preg_match('/^COMPOSER_PATH=(.+)$/m', $envContent, $matches)) {
                $composerPath = trim($matches[1]);
                if ($composerPath && file_exists($composerPath) && is_executable($composerPath)) {
                    return $composerPath;
                }
            }
        }
        
        // 2. Проверяем переменную окружения через config и env
        $composerPath = config('app.composer_path') ?: env('COMPOSER_PATH');
        if ($composerPath && file_exists($composerPath) && is_executable($composerPath)) {
            return $composerPath;
        }
        
        // 3. Пытаемся найти через which (если в PATH)
        $process = Process::run('which composer');
        if ($process->successful()) {
            $path = trim($process->output());
            if ($path && file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // 4. Проверяем пользовательский путь ~/bin/composer (для shared-хостинга)
        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '~');
        
        // Проверяем несколько вариантов путей (прямой путь имеет приоритет)
        $possiblePaths = [
            '/home/d/dsc23ytp/bin/composer', // Прямой путь для этого сервера (приоритет)
        ];
        
        // Добавляем путь на основе HOME
        if ($homeDir && $homeDir !== '~') {
            $possiblePaths[] = $homeDir . '/bin/composer';
        }
        
        foreach ($possiblePaths as $userComposerPath) {
            // Нормализуем путь (убираем ~ если есть)
            $normalizedPath = str_replace('~', $homeDir, $userComposerPath);
            if (file_exists($normalizedPath) && is_executable($normalizedPath)) {
                return $normalizedPath;
            }
        }
        
        // 5. Стандартный путь (последний вариант)
        return 'composer';
    }
    
    /**
     * Вывод информационного сообщения
     *
     * @param string $message
     * @return void
     */
    protected function info(string $message): void
    {
        Log::info('[Deploy] ' . $message);
    }
    
    /**
     * Вывод предупреждения
     *
     * @param string $message
     * @return void
     */
    protected function warn(string $message): void
    {
        Log::warning('[Deploy] ' . $message);
    }
    
    /**
     * Вывод ошибки
     *
     * @param string $message
     * @return void
     */
    protected function error(string $message): void
    {
        Log::error('[Deploy] ' . $message);
    }
}

