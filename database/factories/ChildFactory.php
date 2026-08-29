<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guardian_id' => User::factory()->guardian(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'class' => fake()->randomElement(['A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'G1', 'G2', 'G3']),
        ];
    }
}
