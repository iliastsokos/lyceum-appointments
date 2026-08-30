<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GuardianController as AdminGuardianController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TeacherAvailabilityController as AdminTeacherAvailabilityController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guardian\AppointmentController as GuardianAppointmentController;
use App\Http\Controllers\Guardian\BookingController;
use App\Http\Controllers\Guardian\ChildController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Teacher\AppointmentController as TeacherAppointmentController;
use App\Http\Controllers\Teacher\AvailabilityController;
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

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

Route::middleware(['auth', 'role:guardian'])->prefix('guardian')->name('guardian.')->group(function () {
    Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');

    Route::get('/children/create', [ChildController::class, 'create'])->name('children.create');
    Route::post('/children', [ChildController::class, 'store'])->name('children.store');
    Route::get('/children/{child}/edit', [ChildController::class, 'edit'])->name('children.edit');
    Route::put('/children/{child}', [ChildController::class, 'update'])->name('children.update');
    Route::delete('/children/{child}', [ChildController::class, 'destroy'])->name('children.destroy');

    Route::get('/book', [BookingController::class, 'teachers'])->name('book.teachers');
    Route::get('/book/{teacher}', [BookingController::class, 'pickDate'])->name('book.date');
    Route::get('/book/{teacher}/slots/{slot}', [BookingController::class, 'confirm'])->name('book.confirm');
    Route::post('/book/{teacher}/slots/{slot}', [BookingController::class, 'store'])->name('book.store');

    Route::get('/appointments', [GuardianAppointmentController::class, 'index'])->name('appointments.index');
    Route::patch('/appointments/{appointment}/cancel', [GuardianAppointmentController::class, 'cancel'])->name('appointments.cancel');
});

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

    Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');
    Route::post('/availability', [AvailabilityController::class, 'store'])->name('availability.store');
    Route::get('/availability/{availability}', [AvailabilityController::class, 'show'])->name('availability.show');
    Route::delete('/availability/{availability}', [AvailabilityController::class, 'destroy'])->name('availability.destroy');
    Route::patch('/availability/slots/{slot}/toggle', [AvailabilityController::class, 'toggleSlot'])->name('availability.slots.toggle');

    Route::get('/appointments', [TeacherAppointmentController::class, 'index'])->name('appointments.index');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/teachers', [AdminTeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/create', [AdminTeacherController::class, 'create'])->name('teachers.create');
    Route::post('/teachers', [AdminTeacherController::class, 'store'])->name('teachers.store');
    Route::get('/teachers/{teacher}/edit', [AdminTeacherController::class, 'edit'])->name('teachers.edit');
    Route::put('/teachers/{teacher}', [AdminTeacherController::class, 'update'])->name('teachers.update');
    Route::patch('/teachers/{teacher}/toggle-status', [AdminTeacherController::class, 'toggleStatus'])->name('teachers.toggle-status');
    Route::patch('/teachers/{teacher}/reset-password', [AdminTeacherController::class, 'resetPassword'])->name('teachers.reset-password');
    Route::delete('/teachers/{teacher}', [AdminTeacherController::class, 'destroy'])->name('teachers.destroy');

    Route::get('/teachers/{teacher}/availability', [AdminTeacherAvailabilityController::class, 'index'])->name('teachers.availability.index');
    Route::post('/teachers/{teacher}/availability', [AdminTeacherAvailabilityController::class, 'store'])->name('teachers.availability.store');
    Route::get('/teachers/{teacher}/availability/{availability}', [AdminTeacherAvailabilityController::class, 'show'])->name('teachers.availability.show');
    Route::delete('/teachers/{teacher}/availability/{availability}', [AdminTeacherAvailabilityController::class, 'destroy'])->name('teachers.availability.destroy');
    Route::patch('/teachers/{teacher}/availability/slots/{slot}/toggle', [AdminTeacherAvailabilityController::class, 'toggleSlot'])->name('teachers.availability.slots.toggle');

    Route::get('/guardians', [AdminGuardianController::class, 'index'])->name('guardians.index');
    Route::get('/guardians/create', [AdminGuardianController::class, 'create'])->name('guardians.create');
    Route::post('/guardians', [AdminGuardianController::class, 'store'])->name('guardians.store');
    Route::get('/guardians/{guardian}/edit', [AdminGuardianController::class, 'edit'])->name('guardians.edit');
    Route::put('/guardians/{guardian}', [AdminGuardianController::class, 'update'])->name('guardians.update');
    Route::patch('/guardians/{guardian}/toggle-status', [AdminGuardianController::class, 'toggleStatus'])->name('guardians.toggle-status');
    Route::patch('/guardians/{guardian}/reset-password', [AdminGuardianController::class, 'resetPassword'])->name('guardians.reset-password');
    Route::delete('/guardians/{guardian}', [AdminGuardianController::class, 'destroy'])->name('guardians.destroy');

    Route::get('/school-classes', [SchoolClassController::class, 'index'])->name('school-classes.index');
    Route::post('/school-classes', [SchoolClassController::class, 'store'])->name('school-classes.store');
    Route::put('/school-classes/{school_class}', [SchoolClassController::class, 'update'])->name('school-classes.update');
    Route::delete('/school-classes/{school_class}', [SchoolClassController::class, 'destroy'])->name('school-classes.destroy');

    Route::post('/system/migrate', [SystemController::class, 'migrate'])->name('system.migrate');

    Route::get('/imports', function () {
        return view('admin.imports.index');
    })->name('imports.index');
    Route::get('/imports/history', [ImportController::class, 'history'])->name('imports.history');
    Route::get('/imports/history/{batch}', [ImportController::class, 'historyShow'])->name('imports.history.show');
    Route::get('/imports/history/{batch}/errors.csv', [ImportController::class, 'downloadErrorReport'])->name('imports.history.errors');
    Route::get('/imports/history/{batch}/credentials.csv', [ImportController::class, 'downloadCredentials'])->name('imports.history.credentials');
    Route::get('/imports/templates/teachers', [ImportController::class, 'teacherTemplate'])->name('imports.templates.teachers');
    Route::get('/imports/templates/guardians', [ImportController::class, 'guardianTemplate'])->name('imports.templates.guardians');
    Route::get('/imports/{type}', [ImportController::class, 'show'])->whereIn('type', ['teachers', 'guardians'])->name('imports.show');
    Route::post('/imports/{type}/preview', [ImportController::class, 'preview'])->whereIn('type', ['teachers', 'guardians'])->name('imports.preview');
    Route::post('/imports/{type}/commit', [ImportController::class, 'commit'])->whereIn('type', ['teachers', 'guardians'])->name('imports.commit');
});

require __DIR__.'/auth.php';
