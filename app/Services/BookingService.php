<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Child;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
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

        try {
            return DB::transaction(function () use ($slot, $guardian, $child) {
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
                    'booked_at' => now(),
                ]);

                $lockedSlot->update(['status' => SlotStatus::Booked]);

                Notification::create([
                    'user_id' => $lockedSlot->teacher_id,
                    'type' => 'appointment_booked',
                    'title' => 'New appointment',
                    'message' => sprintf(
                        'Guardian %s booked an appointment for %s at %s.',
                        $guardian->full_name,
                        $child->full_name,
                        substr($lockedSlot->start_time, 0, 5),
                    ),
                ]);

                return $appointment;
            });
        } catch (QueryException $e) {
            // Defense in depth: if two requests somehow both pass the status
            // check (should not happen under lockForUpdate, but the unique
            // constraint on active_slot_id is the hard backstop), the second
            // insert fails here instead of creating a duplicate booking.
            if ($this->isUniqueConstraintViolation($e) || $this->isLockTimeout($e)) {
                throw new SlotUnavailableException;
            }

            throw $e;
        }
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

        return DB::transaction(function () use ($appointment, $reason) {
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

            Notification::create([
                'user_id' => $locked->teacher_id,
                'type' => 'appointment_cancelled',
                'title' => 'Appointment cancelled',
                'message' => sprintf(
                    'Guardian %s cancelled the appointment at %s.',
                    $locked->guardian->full_name,
                    $slot ? substr($slot->start_time, 0, 5) : '',
                ),
            ]);

            return $locked;
        });
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }

    private function isLockTimeout(QueryException $e): bool
    {
        // MySQL/MariaDB: 1205 = lock wait timeout exceeded, 1213 = deadlock found.
        return in_array((int) ($e->errorInfo[1] ?? 0), [1205, 1213], true);
    }
}
