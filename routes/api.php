<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RetentionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/stores', [StoreController::class, 'getNearbyStores']);
Route::post('/stores/claim', [StoreController::class, 'claimStore']);
Route::get('/stocks', [StockController::class, 'index']);
Route::get('/retensi', [RetentionController::class, 'getRetentionData']);