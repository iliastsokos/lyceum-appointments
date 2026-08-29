<?php

namespace Database\Factories;

use App\Enums\SlotStatus;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentSlot>
 */
class AppointmentSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * teacher_id/availability_id use lazy factory relations (not eager
     * ->create() calls) so that when a caller overrides them — which every
     * real usage in this codebase does, to keep a slot consistent with a
     * specific availability window — Laravel discards the unused default
     * instead of creating orphaned rows as a side effect.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'availability_id' => Availability::factory(),
            'date' => today()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '09:05:00',
            'status' => SlotStatus::Available,
        ];
    }
}
