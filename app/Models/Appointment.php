<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Fillable(['slot_id', 'active_slot_id', 'teacher_id', 'guardian_id', 'child_id', 'status', 'date', 'start_time', 'end_time', 'booked_at', 'cancelled_at', 'cancelled_by', 'cancellation_reason'])]
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
     * Mark every still-"new" appointment whose end time has passed as
     * completed. Nothing else ever sets Completed, and there's no cron on
     * the hosts this runs on, so this is called lazily at the top of every
     * page that lists appointments — a single cheap UPDATE that is a no-op
     * almost every time. A failure (e.g. a momentary SQLite lock) is logged
     * and ignored: the next page load simply catches up.
     */
    public static function completePast(): void
    {
        try {
            static::query()
                ->where('status', AppointmentStatus::New)
                ->where(fn (Builder $q) => $q
                    ->where('date', '<', today()->toDateString())
                    ->orWhere(fn (Builder $q) => $q
                        ->where('date', today()->toDateString())
                        ->where('end_time', '<=', now()->format('H:i:s'))))
                ->update(['status' => AppointmentStatus::Completed, 'active_slot_id' => null]);
        } catch (Throwable $e) {
            Log::warning('Could not mark past appointments as completed.', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Upcoming appointments first (today included), soonest first; then
     * past ones, most recent first — so the list opens on what's next
     * instead of on the oldest history.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeUpcomingFirst(Builder $query): void
    {
        $today = today()->toDateString();

        $query
            ->orderByRaw('CASE WHEN date >= ? THEN 0 ELSE 1 END', [$today])
            ->orderByRaw('CASE WHEN date >= ? THEN date END ASC', [$today])
            ->orderByRaw('CASE WHEN date >= ? THEN start_time END ASC', [$today])
            ->orderByRaw('CASE WHEN date < ? THEN date END DESC', [$today])
            ->orderByRaw('CASE WHEN date < ? THEN start_time END DESC', [$today]);
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse("{$this->date->toDateString()} {$this->start_time}");
    }

    /**
     * Whether it can still be cancelled: still "new" and not yet started.
     */
    public function isCancellable(): bool
    {
        return $this->status === AppointmentStatus::New && $this->startsAt()->isFuture();
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Who cancelled this appointment, worded for "Ακυρώθηκε από …" in the
     * appointment lists, or null if it isn't cancelled or the canceller is
     * unknown (cancelled before cancelled_by existed, or that user has since
     * been deleted).
     */
    public function cancelledByLabel(): ?string
    {
        if ($this->status !== AppointmentStatus::Cancelled || $this->cancelled_by === null) {
            return null;
        }

        return match ($this->cancelled_by) {
            $this->guardian_id => 'τον κηδεμόνα',
            $this->teacher_id => 'τον/την εκπαιδευτικό',
            default => 'τη Διεύθυνση',
        };
    }
}
