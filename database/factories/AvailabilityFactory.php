<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'date' => today()->addDay()->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => AvailabilityStatus::Active,
        ];
    }
}
