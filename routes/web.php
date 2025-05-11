<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ZohoController;

// Маршруты для неавторизованных пользователей
Route::get('/auth/zoho', [ZohoController::class, 'showAuth']);                      // Show the authorization page
Route::get('/oauth2callback', [ZohoController::class, 'callback']);                 // Handle the callback from Zoho

// Защищенные маршруты
Route::middleware(['zoho.auth'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('ZohoForm');                                   // Show the form
    });
    Route::post('/api/zoho/create', [ZohoController::class, 'createRecords']);      // Create records
    Route::get('/api/zoho/deal-stages', [ZohoController::class, 'getDealStages']);  // Get deal stages
});
