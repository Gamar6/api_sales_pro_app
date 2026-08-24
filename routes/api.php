<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\ReadRetentionExcelController;
use Illuminate\Support\Facades\Route;

// 1. Route Publik (Bisa diakses siapa saja)
Route::post('/login', [AuthController::class, 'login']);

// 2. Route Terkunci (Wajib membawa Bearer Token yang valid)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/stores', [StoreController::class, 'getNearbyStores']);
    Route::get('/stores_retention', [ReadRetentionExcelController::class, 'getRetentionStores']);
    Route::post('/stores/claim', [StoreController::class, 'claimStore']);
    Route::get('/retensi', [RetentionController::class, 'getRetentionStores']);
    Route::get('/stocks', [StockController::class, 'index']);
    });