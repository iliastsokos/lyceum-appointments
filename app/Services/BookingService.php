<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Child;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Atomically book a slot for a guardian's child.
     *
     * @throws ValidationException
     * @throws SlotUnavailableException
     */
    public function book(AppointmentSlot $slot, User $guardian, Child $child): Appointment
    {
        if (! $guardian->isGuardian()) {
            throw ValidationException::withMessages(['guardian' => 'Μόνο οι κηδεμόνες μπορούν να κλείνουν ραντεβού.']);
        }

        if ($child->guardian_id !== $guardian->id) {
            throw ValidationException::withMessages(['child' => 'Μπορείτε να κλείσετε ραντεβού μόνο για τα δικά σας παιδιά.']);
        }

        $slotStart = Carbon::parse("{$slot->date->toDateString()} {$slot->start_time}");

        if ($slotStart->isPast()) {
            throw ValidationException::withMessages(['slot' => 'Αυτή η ώρα έχει περάσει και δεν μπορεί πλέον να κλειστεί.']);
        }

        $appointment = $this->runInTransactionWithLockRetry(function () use ($slot, $guardian, $child) {
            $lockedSlot = AppointmentSlot::where('id', $slot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSlot->status !== SlotStatus::Available) {
                throw new SlotUnavailableException;
            }

            $appointment = Appointment::create([
                'slot_id' => $lockedSlot->id,
                'active_slot_id' => $lockedSlot->id,
                'teacher_id' => $lockedSlot->teacher_id,
                'guardian_id' => $guardian->id,
                'child_id' => $child->id,
                'status' => AppointmentStatus::New,
                'date' => $lockedSlot->date,
                'start_time' => $lockedSlot->start_time,
                'end_time' => $lockedSlot->end_time,
                'booked_at' => now(),
            ]);

            $lockedSlot->update(['status' => SlotStatus::Booked]);

            return $appointment;
        });

        // Notified only after the booking is safely committed: sending mail
        // can take anywhere from milliseconds to several seconds depending
        // on the school's SMTP server, and SQLite has exactly one writer for
        // the whole database file — holding that lock open for the mail
        // round-trip was blocking every other concurrent write in the app
        // (including unrelated bookings and session writes) for as long as
        // the mail server took to respond.
        $this->notifications->send(
            $appointment->teacher,
            'appointment_booked',
            'Νέο ραντεβού',
            sprintf(
                'Ο κηδεμόνας %s έκλεισε ραντεβού για τον/την %s στις %s και ώρα %s.',
                $guardian->full_name,
                $child->full_name,
                $appointment->date->translatedFormat('d/m/Y'),
                substr($appointment->start_time, 0, 5),
            ),
        );

        return $appointment;
    }

    /**
     * Cancel a guardian's own appointment and free the slot for rebooking.
     *
     * @throws ValidationException
     */
    public function cancel(Appointment $appointment, User $guardian, ?string $reason = null): Appointment
    {
        if ($appointment->guardian_id !== $guardian->id) {
            throw ValidationException::withMessages(['appointment' => 'Μπορείτε να ακυρώσετε μόνο τα δικά σας ραντεβού.']);
        }

        $locked = $this->runInTransactionWithLockRetry(function () use ($appointment, $reason) {
            $locked = Appointment::where('id', $appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === AppointmentStatus::Cancelled) {
                throw ValidationException::withMessages(['appointment' => 'Αυτό το ραντεβού έχει ήδη ακυρωθεί.']);
            }

            $locked->update([
                'status' => AppointmentStatus::Cancelled,
                'active_slot_id' => null,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $slot = AppointmentSlot::where('id', $locked->slot_id)->lockForUpdate()->first();

            if ($slot && $slot->status === SlotStatus::Booked) {
                $slot->update(['status' => SlotStatus::Available]);
            }

            return $locked;
        });

        // See the matching comment in book(): notifying only after commit
        // keeps the mail round-trip from holding SQLite's single write lock
        // open. $locked->date/start_time are its own denormalized columns
        // (survive even if the slot is later deleted), so no slot lookup is
        // needed here at all any more.
        $this->notifications->send(
            $locked->teacher,
            'appointment_cancelled',
            'Ακύρωση ραντεβού',
            sprintf(
                'Ο κηδεμόνας %s ακύρωσε το ραντεβού στις %s και ώρα %s.',
                $locked->guardian->full_name,
                $locked->date->translatedFormat('d/m/Y'),
                substr($locked->start_time, 0, 5),
            ),
        );

        return $locked;
    }

    /**
     * Run $callback inside a DB transaction, retrying with a short
     * randomized backoff if the database reports a lock conflict.
     *
     * SQLite has no row-level locking (lockForUpdate() is a no-op on it),
     * so a transaction's very first read can already be based on a
     * snapshot that's stale by the time it tries to write — a totally
     * unrelated slot committed by another connection in between is enough
     * to make SQLite refuse the write outright ("database is locked"), not
     * just a genuine same-slot race. A plain immediate retry (which is all
     * Laravel's own `$attempts` parameter on DB::transaction() does) isn't
     * reliable here: two callers retrying in lockstep with no delay between
     * attempts can keep re-colliding. The randomized backoff below is what
     * actually lets one side win.
     *
     * @template TReturn
     *
     * @param  \Closure(): TReturn  $callback
     * @return TReturn
     *
     * @throws SlotUnavailableException
     */
    private function runInTransactionWithLockRetry(\Closure $callback, int $maxAttempts = 6): mixed
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                $isLockTimeout = $this->isLockTimeout($e);

                if ($attempt === $maxAttempts || ! $isLockTimeout) {
                    if ($isLockTimeout || $this->isUniqueConstraintViolation($e)) {
                        throw new SlotUnavailableException;
                    }

                    throw $e;
                }

                usleep(random_int(5_000, 40_000));
            }
        }
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }

    private function isLockTimeout(QueryException $e): bool
    {
        // MySQL/MariaDB: 1205 = lock wait timeout exceeded, 1213 = deadlock found.
        // SQLite: 5 = SQLITE_BUSY (another connection holds the write lock
        // past busy_timeout), 6 = SQLITE_LOCKED (a conflict within the same
        // connection, e.g. two statements in one transaction).
        return in_array((int) ($e->errorInfo[1] ?? 0), [1205, 1213, 5, 6], true);
    }
}
