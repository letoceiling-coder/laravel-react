<?php

use App\Http\Controllers\Api\DeployController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Deploy endpoint - автоматическое обновление кода на сервере
// Требует заголовок X-Deploy-Token для авторизации
Route::post('/deploy', [DeployController::class, 'handle'])->name('api.deploy');

