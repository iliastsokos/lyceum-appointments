<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Applies to every Password::defaults() call across the app
        // (registration, password reset, profile password change, forced
        // first-login change, admin creation). Deliberately minimal by
        // request: any password of at least 4 characters is accepted, with
        // no case/letter/number composition requirements.
        Password::defaults(fn () => Password::min(4));
    }
}
