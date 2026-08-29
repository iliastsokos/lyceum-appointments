<?php

namespace Database\Factories;

use App\Enums\SlotStatus;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentSlot>
 */
class AppointmentSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $availability = Availability::factory()->create();

        return [
            'teacher_id' => $availability->teacher_id,
            'availability_id' => $availability->id,
            'date' => $availability->date,
            'start_time' => $availability->start_time,
            'end_time' => '17:05:00',
            'status' => SlotStatus::Available,
        ];
    }
}
