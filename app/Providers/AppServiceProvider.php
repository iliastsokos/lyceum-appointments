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
        // first-login change, admin creation) — this app handles personal
        // data about minors, so the plain 8-character Laravel default is
        // strengthened here rather than per call site.
        // Deliberately does not add ->uncompromised(): that check calls an
        // external API (HaveIBeenPwned) on every password submission, which
        // would make registration/reset depend on outbound internet access
        // being available and fast — not guaranteed on shared hosting, and
        // not something to introduce as a hard dependency for core auth.
        Password::defaults(fn () => Password::min(10)
            ->mixedCase()
            ->numbers()
        );
    }
}
