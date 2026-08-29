<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalTeachers' => User::where('role', UserRole::Teacher)->count(),
            'totalGuardians' => User::where('role', UserRole::Guardian)->count(),
            'totalStudents' => Child::count(),
        ]);
    }
}
