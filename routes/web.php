<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ZohoController;

// Маршруты для неавторизованных пользователей
Route::get('/auth/zoho', [ZohoController::class, 'showAuth']);                      // Показать страницу авторизации
Route::get('/oauth2callback', [ZohoController::class, 'callback']);                 // Обработать коллбек от Zoho

// Защищенные маршруты
Route::middleware(['zoho.auth'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('ZohoForm');                                   // Показать форму
    });
    Route::post('/api/zoho/create', [ZohoController::class, 'createRecords']);      // Создать записи
    Route::get('/api/zoho/deal-stages', [ZohoController::class, 'getDealStages']);  // Получить стадии сделок
});
