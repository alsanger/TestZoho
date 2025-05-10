<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ZohoController;

// Маршруты для неавторизованных пользователей
Route::get('/auth/zoho', [ZohoController::class, 'showAuth']);
Route::get('/oauth2callback', [ZohoController::class, 'callback']);

// Защищенные маршруты
Route::middleware(['zoho.auth'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('ZohoForm');
    });
    Route::post('/api/zoho/create', [ZohoController::class, 'createRecords']);
    Route::get('/api/zoho/deal-stages', [ZohoController::class, 'getDealStages']);
});
