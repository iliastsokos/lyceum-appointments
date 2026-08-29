<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date,
            'start_time' => $availability->start_time,
            'status' => SlotStatus::Booked,
        ]);

        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        return [
            'slot_id' => $slot->id,
            'active_slot_id' => $slot->id,
            'teacher_id' => $slot->teacher_id,
            'guardian_id' => $guardian->id,
            'child_id' => $child->id,
            'status' => AppointmentStatus::New,
            'booked_at' => now(),
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
            'active_slot_id' => null,
            'cancelled_at' => now(),
        ]);
    }
}
