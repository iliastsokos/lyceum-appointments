<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['teacher_id', 'date', 'start_time', 'end_time', 'status'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    protected $table = 'availability';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // See the identical comment in AppointmentSlot::casts() — a plain
            // 'date' cast round-trips through a full datetime string that
            // SQLite (unlike MySQL) stores and compares literally.
            'date' => 'date:Y-m-d',
            'status' => AvailabilityStatus::class,
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
     * @return HasMany<AppointmentSlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class, 'availability_id');
    }
}
