<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\SchoolClass;
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
            // The migration seeds a default set of classes, so this is
            // normally never empty — the literal fallback only matters for
            // a database that's had every class deleted.
            'class' => fake()->randomElement(SchoolClass::names() ?: ['A1']),
        ];
    }
}
