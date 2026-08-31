<?php

namespace Tests\Feature\Booking;

use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Child;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * The mandatory concurrent-booking race test (spec §10/§36).
 *
 * Deliberately does NOT use RefreshDatabase: that trait wraps each test in a
 * transaction that is rolled back afterwards, which would make this test's
 * fixture rows invisible to the two separate worker *processes* launched
 * below (their own DB connections would never see uncommitted data). A real
 * race between two independent processes needs real committed rows, so this
 * test creates its own data with normal auto-committed writes and cleans up
 * manually in tearDown().
 */
class ConcurrentBookingTest extends TestCase
{
    /** @var array<int, int> */
    private array $createdUserIds = [];

    private ?Availability $availability = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Artisan::call('migrate', ['--force' => true]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdUserIds !== []) {
            Appointment::whereIn('teacher_id', $this->createdUserIds)
                ->orWhereIn('guardian_id', $this->createdUserIds)
                ->delete();
        }

        if ($this->availability) {
            AppointmentSlot::where('availability_id', $this->availability->id)->delete();
            $this->availability->delete();
        }

        if ($this->createdUserIds !== []) {
            Child::whereIn('guardian_id', $this->createdUserIds)->delete();
            User::whereIn('id', $this->createdUserIds)->delete();
        }

        parent::tearDown();
    }

    public function test_two_concurrent_booking_attempts_on_the_same_slot_result_in_exactly_one_appointment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        $childA = Child::factory()->for($guardianA, 'guardian')->create();
        $childB = Child::factory()->for($guardianB, 'guardian')->create();

        $this->createdUserIds = [$teacher->id, $guardianA->id, $guardianB->id];

        $this->availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->addDay()->toDateString(),
        ]);

        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $this->availability->id,
            'date' => $this->availability->date,
            'status' => SlotStatus::Available,
        ]);

        $scratchDir = sys_get_temp_dir().'/concurrent_booking_test_'.uniqid();
        mkdir($scratchDir);

        $readyA = "{$scratchDir}/ready_a";
        $readyB = "{$scratchDir}/ready_b";
        $go = "{$scratchDir}/go";
        $resultA = "{$scratchDir}/result_a";
        $resultB = "{$scratchDir}/result_b";

        $workerScript = realpath(__DIR__.'/../../Support/concurrent_booking_worker.php');
        $env = $this->workerEnvironment();

        $processA = new Process([PHP_BINARY, $workerScript, (string) $slot->id, (string) $guardianA->id, (string) $childA->id, $readyA, $go, $resultA], null, $env);
        $processB = new Process([PHP_BINARY, $workerScript, (string) $slot->id, (string) $guardianB->id, (string) $childB->id, $readyB, $go, $resultB], null, $env);
        $processA->setTimeout(30);
        $processB->setTimeout(30);

        $processA->start();
        $processB->start();

        $deadline = microtime(true) + 10;
        while ((! file_exists($readyA) || ! file_exists($readyB)) && microtime(true) < $deadline) {
            usleep(1000);
        }

        $this->assertFileExists($readyA, 'Worker A did not become ready in time. stderr: '.$processA->getErrorOutput());
        $this->assertFileExists($readyB, 'Worker B did not become ready in time. stderr: '.$processB->getErrorOutput());

        // Release both workers as close together as possible so they race
        // for the same row lock inside BookingService::book().
        file_put_contents($go, '1');

        $processA->wait();
        $processB->wait();

        $this->assertFileExists($resultA, 'Worker A produced no result. stderr: '.$processA->getErrorOutput());
        $this->assertFileExists($resultB, 'Worker B produced no result. stderr: '.$processB->getErrorOutput());

        $resultAContents = file_get_contents($resultA);
        $resultBContents = file_get_contents($resultB);

        $this->assertFalse(str_starts_with($resultAContents, 'TIMEOUT'), "Worker A timed out.\nstderr: ".$processA->getErrorOutput());
        $this->assertFalse(str_starts_with($resultBContents, 'TIMEOUT'), "Worker B timed out.\nstderr: ".$processB->getErrorOutput());

        $outcomes = [$resultAContents, $resultBContents];
        $successes = array_values(array_filter($outcomes, fn ($r) => str_starts_with($r, 'OK:')));
        $failures = array_values(array_filter($outcomes, fn ($r) => str_starts_with($r, 'FAIL:')));

        $debug = "A: {$resultAContents}\nB: {$resultBContents}";

        $this->assertCount(1, $successes, "Expected exactly one successful booking.\n{$debug}");
        $this->assertCount(1, $failures, "Expected exactly one failed booking.\n{$debug}");

        $this->assertStringContainsString('SlotUnavailableException', $failures[0]);
        $this->assertStringContainsString('μόλις κλείστηκε από άλλον χρήστη', $failures[0]);

        // The database must never contain more than one active appointment
        // for this slot — this is the actual correctness guarantee.
        $this->assertSame(1, Appointment::where('slot_id', $slot->id)->count());
        $this->assertSame(SlotStatus::Booked, $slot->fresh()->status);

        @unlink($readyA);
        @unlink($readyB);
        @unlink($go);
        @unlink($resultA);
        @unlink($resultB);
        @rmdir($scratchDir);
    }

    public function test_two_concurrent_booking_attempts_on_different_slots_of_the_same_teacher_both_succeed(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        $childA = Child::factory()->for($guardianA, 'guardian')->create();
        $childB = Child::factory()->for($guardianB, 'guardian')->create();

        $this->createdUserIds = [$teacher->id, $guardianA->id, $guardianB->id];

        $this->availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->addDay()->toDateString(),
        ]);

        $slotA = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $this->availability->id,
            'date' => $this->availability->date,
            'start_time' => '09:00:00',
            'end_time' => '09:05:00',
            'status' => SlotStatus::Available,
        ]);
        $slotB = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $this->availability->id,
            'date' => $this->availability->date,
            'start_time' => '09:05:00',
            'end_time' => '09:10:00',
            'status' => SlotStatus::Available,
        ]);

        $scratchDir = sys_get_temp_dir().'/concurrent_booking_test_'.uniqid();
        mkdir($scratchDir);

        $readyA = "{$scratchDir}/ready_a";
        $readyB = "{$scratchDir}/ready_b";
        $go = "{$scratchDir}/go";
        $resultA = "{$scratchDir}/result_a";
        $resultB = "{$scratchDir}/result_b";

        $workerScript = realpath(__DIR__.'/../../Support/concurrent_booking_worker.php');
        $env = $this->workerEnvironment();

        $processA = new Process([PHP_BINARY, $workerScript, (string) $slotA->id, (string) $guardianA->id, (string) $childA->id, $readyA, $go, $resultA], null, $env);
        $processB = new Process([PHP_BINARY, $workerScript, (string) $slotB->id, (string) $guardianB->id, (string) $childB->id, $readyB, $go, $resultB], null, $env);
        $processA->setTimeout(30);
        $processB->setTimeout(30);

        $processA->start();
        $processB->start();

        $deadline = microtime(true) + 10;
        while ((! file_exists($readyA) || ! file_exists($readyB)) && microtime(true) < $deadline) {
            usleep(1000);
        }

        $this->assertFileExists($readyA, 'Worker A did not become ready in time. stderr: '.$processA->getErrorOutput());
        $this->assertFileExists($readyB, 'Worker B did not become ready in time. stderr: '.$processB->getErrorOutput());

        // Release both workers as close together as possible so they race
        // to write to the same SQLite database file (different rows, but
        // SQLite has no row-level locking — the whole file serializes
        // writes) inside BookingService::book().
        file_put_contents($go, '1');

        $processA->wait();
        $processB->wait();

        $this->assertFileExists($resultA, 'Worker A produced no result. stderr: '.$processA->getErrorOutput());
        $this->assertFileExists($resultB, 'Worker B produced no result. stderr: '.$processB->getErrorOutput());

        $resultAContents = file_get_contents($resultA);
        $resultBContents = file_get_contents($resultB);

        $debug = "A: {$resultAContents}\nB: {$resultBContents}";

        $this->assertStringStartsWith('OK:', $resultAContents, "Worker A should succeed booking a different slot.\n{$debug}");
        $this->assertStringStartsWith('OK:', $resultBContents, "Worker B should succeed booking a different slot.\n{$debug}");

        $this->assertSame(1, Appointment::where('slot_id', $slotA->id)->count());
        $this->assertSame(1, Appointment::where('slot_id', $slotB->id)->count());

        @unlink($readyA);
        @unlink($readyB);
        @unlink($go);
        @unlink($resultA);
        @unlink($resultB);
        @rmdir($scratchDir);
    }

    /**
     * @return array<string, string>
     */
    private function workerEnvironment(): array
    {
        $connectionName = config('database.default');
        $db = config("database.connections.{$connectionName}");

        $base = getenv();
        $base = is_array($base) ? $base : [];

        return array_merge($base, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => $connectionName,
            'DB_HOST' => (string) ($db['host'] ?? '127.0.0.1'),
            'DB_PORT' => (string) ($db['port'] ?? '3306'),
            'DB_DATABASE' => (string) $db['database'],
            'DB_USERNAME' => (string) ($db['username'] ?? 'root'),
            'DB_PASSWORD' => (string) ($db['password'] ?? ''),
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ]);
    }
}
