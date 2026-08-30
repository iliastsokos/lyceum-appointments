<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['slot_id', 'active_slot_id', 'teacher_id', 'guardian_id', 'child_id', 'status', 'date', 'start_time', 'end_time', 'booked_at', 'cancelled_at', 'cancellation_reason'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            // Same explicit Y-m-d format as AppointmentSlot::casts(), and for
            // the same reason: this model is shared with the sqlite-migration
            // branch, where the plain 'date' cast would serialize a full
            // datetime string instead of just a date on write.
            'date' => 'date:Y-m-d',
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AppointmentSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    /**
     * @return BelongsTo<Child, $this>
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }
}
