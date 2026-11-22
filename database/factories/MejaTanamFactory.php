<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MejaTanamFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nomor_meja' => fake()->unique()->numberBetween(1, 100),
            'kapasitas_lubang' => fake()->numberBetween(50, 500),
            'status' => fake()->randomElement(['kosong', 'terisi']),
        ];
    }
}
