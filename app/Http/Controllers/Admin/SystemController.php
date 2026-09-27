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
 *
 * It also clears the framework's config/route/view/event caches — the other
 * half of `php artisan optimize:clear` that such hosts can't run either —
 * since a stale cached route list would hide routes added by the new
 * version. The application cache (cache:clear) is deliberately left alone.
 */
class SystemController extends Controller
{
    private const COMMANDS = [
        'migrate' => ['--force' => true],
        'config:clear' => [],
        'route:clear' => [],
        'view:clear' => [],
        'event:clear' => [],
    ];

    public function migrate(): RedirectResponse
    {
        $output = [];

        foreach (self::COMMANDS as $command => $options) {
            Artisan::call($command, $options);
            $output[] = trim(Artisan::output());
        }

        return redirect()->route('admin.dashboard')
            ->with('status', 'migrations-run')
            ->with('migrateOutput', implode("\n", array_filter($output)));
    }
}
