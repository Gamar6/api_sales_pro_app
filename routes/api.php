<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/stores', [StoreController::class, 'getNearbyStores']);
Route::post('/stores/claim', [StoreController::class, 'claimStore']);