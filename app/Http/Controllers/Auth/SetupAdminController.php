<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetupAdminRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * One-time bootstrap for hosts where the administrator cannot reach a
 * terminal or Plesk Scheduled Tasks to run `php artisan app:create-admin`
 * (e.g. a subscription-level, non-owner Plesk account). This form only
 * exists until the very first user is created — every action here 404s
 * unconditionally once any user exists, so it can't be used to create a
 * second admin or reached at all after initial setup.
 */
class SetupAdminController extends Controller
{
    public function create(): View
    {
        abort_if(User::query()->exists(), 404);

        return view('auth.setup-admin');
    }

    public function store(SetupAdminRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $admin = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'must_change_password' => false,
        ]);

        Auth::login($admin);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
