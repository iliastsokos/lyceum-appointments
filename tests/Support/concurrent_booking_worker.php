<?php

use App\Models\AppointmentSlot;
use App\Models\Child;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Standalone worker process for the concurrent double-booking test.
 *
 * This is deliberately NOT part of the application (app/) — it exists only
 * so the test suite can launch two genuinely separate OS processes that
 * both race to book the same slot through the real BookingService, which is
 * the only way to prove the row-locking protection under real concurrency
 * rather than a simulated/serial approximation.
 *
 * Usage: php concurrent_booking_worker.php <slotId> <guardianId> <childId> <readyFile> <goFile> <resultFile>
 */

require __DIR__.'/../../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__.'/../../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

[$script, $slotId, $guardianId, $childId, $readyFile, $goFile, $resultFile] = $argv;

// Signal readiness, then wait for the parent test process to release both
// workers at (as close as possible to) the same moment.
file_put_contents($readyFile, '1');

$waited = 0;
while (! file_exists($goFile)) {
    usleep(2000);
    $waited++;

    if ($waited > 5000) {
        file_put_contents($resultFile, 'TIMEOUT_WAITING_FOR_GO');
        exit(1);
    }
}

try {
    $slot = AppointmentSlot::findOrFail((int) $slotId);
    $guardian = User::findOrFail((int) $guardianId);
    $child = Child::findOrFail((int) $childId);

    $appointment = app(BookingService::class)->book($slot, $guardian, $child);

    file_put_contents($resultFile, 'OK:'.$appointment->id);
} catch (Throwable $e) {
    file_put_contents($resultFile, 'FAIL:'.get_class($e).':'.$e->getMessage());
}
