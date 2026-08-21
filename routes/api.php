<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RetentionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReadRetentionExcelController;

Route::get('/stores_retention', [ReadRetentionExcelController::class, 'getRetentionStores']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/stores', [StoreController::class, 'getNearbyStores']);
Route::post('/stores/claim', [StoreController::class, 'claimStore']);
Route::get('/stocks', [StockController::class, 'index']);
Route::get('/retensi', [RetentionController::class, 'getRetentionStores']);