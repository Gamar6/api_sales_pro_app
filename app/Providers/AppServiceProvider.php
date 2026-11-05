<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return url('/reset-password/' . $token) . '?' . http_build_query([
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }
}
