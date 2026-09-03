<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\StoreVisitController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/retensi', [RetentionController::class, 'getRetentionStores']);
    Route::get('/stocks', [StockController::class, 'index']);
    Route::get('/visits/active', [StoreVisitController::class, 'getActiveVisit']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::prefix('store-visits')->group(function () {
        Route::post('/claim', [StoreVisitController::class, 'claim']);
        Route::post('/{visit}/submit-report', [StoreVisitController::class, 'submitReport']);
        Route::post('/{visit}/cancel', [StoreVisitController::class, 'cancel']);
        Route::get('/history', [StoreVisitController::class, 'history']);
    });
});
