<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;
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

        // Laravel's built-in reset-link email is English-only by default;
        // this app's UI is fully Greek, so the notification content is
        // overridden here instead. The link itself and its 60-minute expiry
        // (config/auth.php's passwords.users.expire) are unchanged.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));

            return (new MailMessage)
                ->subject('Επαναφορά Κωδικού Πρόσβασης')
                ->greeting('Γεια σας,')
                ->line('Λάβατε αυτό το μήνυμα επειδή ζητήθηκε επαναφορά κωδικού για τον λογαριασμό σας.')
                ->action('Επαναφορά Κωδικού', $url)
                ->line('Αυτός ο σύνδεσμος θα λήξει σε 60 λεπτά.')
                ->line('Αν δεν ζητήσατε εσείς επαναφορά κωδικού, δεν χρειάζεται καμία ενέργεια.');
        });

        // Shared across the layout, the mobile bottom-nav, and the top bar so
        // all three always agree on the same tab set — a plain @include
        // can't do this since Blade only shares the *parent's* variables
        // into an included view, never the other way back out.
        View::composer(['layouts.app', 'layouts.navigation', 'components.bottom-nav'], function ($view) {
            $user = auth()->user();

            $navTabs = match (true) {
                $user?->isGuardian() => [
                    ['label' => 'Αρχική', 'route' => 'guardian.dashboard', 'icon' => 'home', 'match' => ['dashboard', 'guardian.dashboard']],
                    ['label' => 'Κλείσε Ραντεβού', 'route' => 'guardian.book.teachers', 'icon' => 'search', 'match' => ['guardian.book.*']],
                    ['label' => 'Τα Ραντεβού μου', 'route' => 'guardian.appointments.index', 'icon' => 'calendar', 'match' => ['guardian.appointments.*']],
                    ['label' => 'Προφίλ', 'route' => 'profile.edit', 'icon' => 'user', 'match' => ['profile.*']],
                ],
                $user?->isTeacher() => [
                    ['label' => 'Αρχική', 'route' => 'teacher.dashboard', 'icon' => 'home', 'match' => ['dashboard', 'teacher.dashboard']],
                    ['label' => 'Διαθεσιμότητα', 'route' => 'teacher.availability.index', 'icon' => 'clock', 'match' => ['teacher.availability.*']],
                    ['label' => 'Ραντεβού', 'route' => 'teacher.appointments.index', 'icon' => 'calendar', 'match' => ['teacher.appointments.*']],
                    ['label' => 'Προφίλ', 'route' => 'profile.edit', 'icon' => 'user', 'match' => ['profile.*']],
                ],
                // Admin's own dashboard is already a hub linking every admin
                // section, so it doesn't get a persistent tab bar.
                default => [],
            };

            $view->with('navTabs', $navTabs);
            $view->with('hasBottomNav', $navTabs !== []);
        });
    }
}
