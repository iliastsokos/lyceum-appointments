<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect the authenticated user to their role-specific dashboard.
     */
    public function index(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            UserRole::Admin => redirect()->route('admin.dashboard'),
            UserRole::Teacher => redirect()->route('teacher.dashboard'),
            UserRole::Guardian => redirect()->route('guardian.dashboard'),
        };
    }
}
