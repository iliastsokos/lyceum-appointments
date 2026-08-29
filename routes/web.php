<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GuardianController as AdminGuardianController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guardian\ChildController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/password/force-change', [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::put('/password/force-change', [ForcePasswordChangeController::class, 'update'])->name('password.force-change.update');
});

Route::middleware(['auth', 'role:guardian'])->prefix('guardian')->name('guardian.')->group(function () {
    Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');

    Route::get('/children/create', [ChildController::class, 'create'])->name('children.create');
    Route::post('/children', [ChildController::class, 'store'])->name('children.store');
    Route::get('/children/{child}/edit', [ChildController::class, 'edit'])->name('children.edit');
    Route::put('/children/{child}', [ChildController::class, 'update'])->name('children.update');
    Route::delete('/children/{child}', [ChildController::class, 'destroy'])->name('children.destroy');
});

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/teachers', [AdminTeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/create', [AdminTeacherController::class, 'create'])->name('teachers.create');
    Route::post('/teachers', [AdminTeacherController::class, 'store'])->name('teachers.store');
    Route::get('/teachers/{teacher}/edit', [AdminTeacherController::class, 'edit'])->name('teachers.edit');
    Route::put('/teachers/{teacher}', [AdminTeacherController::class, 'update'])->name('teachers.update');
    Route::patch('/teachers/{teacher}/toggle-status', [AdminTeacherController::class, 'toggleStatus'])->name('teachers.toggle-status');

    Route::get('/guardians', [AdminGuardianController::class, 'index'])->name('guardians.index');
    Route::get('/guardians/create', [AdminGuardianController::class, 'create'])->name('guardians.create');
    Route::post('/guardians', [AdminGuardianController::class, 'store'])->name('guardians.store');
    Route::get('/guardians/{guardian}/edit', [AdminGuardianController::class, 'edit'])->name('guardians.edit');
    Route::put('/guardians/{guardian}', [AdminGuardianController::class, 'update'])->name('guardians.update');
    Route::patch('/guardians/{guardian}/toggle-status', [AdminGuardianController::class, 'toggleStatus'])->name('guardians.toggle-status');
});

require __DIR__.'/auth.php';
