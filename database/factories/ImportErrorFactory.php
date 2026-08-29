<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ImportError;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportError>
 */
class ImportErrorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_batch_id' => ImportBatch::factory(),
            'row_number' => fake()->numberBetween(2, 200),
            'field' => 'email',
            'error_message' => 'Invalid email',
            'row_data' => ['email' => 'not-an-email'],
        ];
    }
}
