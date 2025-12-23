<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Админ-панель
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/{any}', [AdminController::class, 'index'])->where('any', '.*');
});

// Все остальные маршруты → React frontend или заглушка
// ВАЖНО: Статические файлы (CSS, JS) должны быть доступны напрямую через веб-сервер
// Для этого создайте симлинк: public/assets -> ../frontend/dist/assets
// Или настройте веб-сервер для прямой отдачи файлов из frontend/dist
Route::get('/{any?}', [FrontendController::class, 'index'])->where('any', '.*');
