<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Carbon\Carbon;
use Database\Factories\AppointmentSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['teacher_id', 'availability_id', 'date', 'start_time', 'end_time', 'status'])]
class AppointmentSlot extends Model
{
    /** @use HasFactory<AppointmentSlotFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Explicit Y-m-d format, not just 'date': the plain 'date' cast
            // serializes through the connection's full datetime format when
            // saving (e.g. "2026-08-31 00:00:00"). MySQL's DATE column type
            // silently truncates that back to a date; SQLite has no such
            // coercion and stores the literal string with the time portion,
            // which then fails to compare/match against plain 'Y-m-d' values
            // (including the unique index on teacher_id+date+start_time).
            'date' => 'date:Y-m-d',
            'status' => SlotStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return BelongsTo<Availability, $this>
     */
    public function availability(): BelongsTo
    {
        return $this->belongsTo(Availability::class, 'availability_id');
    }

    /**
     * The active (non-cancelled) appointment for this slot, if any.
     *
     * @return HasOne<Appointment, $this>
     */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'slot_id')->where('status', '!=', 'cancelled');
    }

    /**
     * Whether this slot's start time has already gone by. A slot's stored
     * `status` doesn't change on its own as the clock passes it — this is
     * the "is it actually still bookable right now" check the booking flow
     * uses to hide/gray out today's already-past slots instead of letting a
     * guardian click one only to be told it can't be booked.
     */
    public function hasPassed(): bool
    {
        return Carbon::parse("{$this->date->toDateString()} {$this->start_time}")->isPast();
    }

    /**
     * Whether a guardian could actually book this slot right now.
     */
    public function isBookable(): bool
    {
        return $this->status === SlotStatus::Available && ! $this->hasPassed();
    }
}
