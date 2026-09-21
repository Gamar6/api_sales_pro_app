<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VisitExceptionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\VisitReportController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get(
    '/dashboard',
    [DashboardController::class, 'index']
)->middleware(['auth', 'verified', 'active'])
 ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');

    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');

    Route::get(
        '/visit-reports',
        [VisitReportController::class, 'index']
    )->name('visit-reports');

    Route::get(
        '/product-catalog',
        [ProductCatalogController::class, 'index']
    )->name('product-catalog');
});

Route::middleware([
    'auth',
    'verified',
    'active',
    'role:admin,superadmin',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])
            ->name('users.index');

        Route::post('/users', [UserManagementController::class, 'store'])
            ->name('users.store');

        Route::put('/users/{user}', [UserManagementController::class, 'update'])
            ->name('users.update');

        Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])
            ->name('users.status');

        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
            ->name('users.reset-password');
    });

    Route::middleware([
    'auth',
    'verified',
    'active',
    'role:admin,superadmin',
    ])->group(function () {
        Route::get('/visit_exception', [
            VisitExceptionController::class,
            'index',
        ])->name('visit-exceptions.index');
    });

require __DIR__.'/auth.php';
