<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\StoreVisitController;
use Illuminate\Support\Facades\Route;

// 1. Route Publik (Bisa diakses siapa saja)
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/retensi', [RetentionController::class, 'getRetentionStores']);
    Route::get('/stocks', [StockController::class, 'index']);
    Route::get('/visits/active', [StoreVisitController::class, 'getActiveVisit']);

    // Operasional Klaim & Laporan Kunjungan Sales
    Route::prefix('store-visits')->group(function () {
        Route::post('/claim', [StoreVisitController::class, 'claim']);
        Route::post('/{visit}/submit-report', [StoreVisitController::class, 'submitReport']);
    });
});