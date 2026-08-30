<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * A handful of hosts this app gets deployed to (e.g. a teacher-level, not
 * school-unit, Plesk subscription) offer no SSH and no Scheduled Tasks, so
 * there is no way to run `php artisan migrate` after uploading a new
 * version. This gives an admin a safe, web-reachable equivalent: `migrate`
 * is idempotent (it only ever runs migrations that haven't run yet), and
 * this route is gated by the same `role:admin` middleware as the rest of
 * /admin.
 */
class SystemController extends Controller
{
    public function migrate(): RedirectResponse
    {
        Artisan::call('migrate', ['--force' => true]);

        return redirect()->route('admin.dashboard')
            ->with('status', 'migrations-run')
            ->with('migrateOutput', trim(Artisan::output()));
    }
}
