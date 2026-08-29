<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\SlotStatus;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    private const SLOT_MINUTES = 5;

    /**
     * Create a teacher availability window and generate its 5-minute slots.
     *
     * @throws ValidationException
     */
    public function createAvailability(User $teacher, string $date, string $startTime, string $endTime): Availability
    {
        $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $start = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
        $end = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTime}");

        if ($day->lt(today())) {
            throw ValidationException::withMessages(['date' => 'Δεν μπορείτε να δημιουργήσετε διαθεσιμότητα στο παρελθόν.']);
        }

        if ($start->diffInMinutes($end) % self::SLOT_MINUTES !== 0) {
            throw ValidationException::withMessages(['end_time' => 'Το εύρος διαθεσιμότητας πρέπει να είναι πολλαπλάσιο των 5 λεπτών.']);
        }

        return DB::transaction(function () use ($teacher, $day, $start, $end) {
            $overlaps = Availability::where('teacher_id', $teacher->id)
                ->where('date', $day->toDateString())
                ->where('status', AvailabilityStatus::Active)
                ->where('start_time', '<', $end->format('H:i:s'))
                ->where('end_time', '>', $start->format('H:i:s'))
                ->lockForUpdate()
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['start_time' => 'Αυτό επικαλύπτεται με υπάρχον διάστημα διαθεσιμότητας την ίδια ημέρα.']);
            }

            $availability = Availability::create([
                'teacher_id' => $teacher->id,
                'date' => $day->toDateString(),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'status' => AvailabilityStatus::Active,
            ]);

            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $slotEnd = $cursor->copy()->addMinutes(self::SLOT_MINUTES);

                AppointmentSlot::create([
                    'teacher_id' => $teacher->id,
                    'availability_id' => $availability->id,
                    'date' => $day->toDateString(),
                    'start_time' => $cursor->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'status' => SlotStatus::Available,
                ]);

                $cursor = $slotEnd;
            }

            return $availability;
        });
    }

    /**
     * Remove a future availability window and its slots, provided none of
     * its slots have an active booking (spec §11: never destructively
     * modify already-booked appointments without explicit rules).
     *
     * @throws ValidationException
     */
    public function deleteAvailability(Availability $availability): void
    {
        DB::transaction(function () use ($availability) {
            $hasBookedSlots = $availability->slots()
                ->where('status', SlotStatus::Booked)
                ->lockForUpdate()
                ->exists();

            if ($hasBookedSlots) {
                throw ValidationException::withMessages([
                    'availability' => 'Αυτό το διάστημα διαθεσιμότητας έχει κλεισμένα ραντεβού και δεν μπορεί να αφαιρεθεί. Ακυρώστε πρώτα τα ραντεβού.',
                ]);
            }

            $availability->delete();
        });
    }

    /**
     * Toggle an individual slot between available and disabled.
     * Booked slots cannot be toggled directly.
     *
     * @throws ValidationException
     */
    public function toggleSlot(AppointmentSlot $slot): AppointmentSlot
    {
        return DB::transaction(function () use ($slot) {
            $locked = AppointmentSlot::where('id', $slot->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === SlotStatus::Booked) {
                throw ValidationException::withMessages([
                    'slot' => 'Μια κλεισμένη ώρα δεν μπορεί να απενεργοποιηθεί. Ακυρώστε πρώτα το ραντεβού.',
                ]);
            }

            $locked->status = $locked->status === SlotStatus::Available
                ? SlotStatus::Disabled
                : SlotStatus::Available;

            $locked->save();

            return $locked;
        });
    }
}
