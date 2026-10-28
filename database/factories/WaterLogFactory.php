<?php

namespace Database\Factories;

use App\Models\MejaTanams;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaterLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'meja_id' => MejaTanams::factory(),
            'pH' => fake()->randomFloat(2, 0, 99.99),
            'tds_ppm' => fake()->randomFloat(2, 0, 999999.99),
            'waktu' => fake()->dateTime(),
        ];
    }
}
