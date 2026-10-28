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
            'nomor_meja' => fake()->numberBetween(-10000, 10000),
            'kapasitas_lubang' => fake()->numberBetween(-10000, 10000),
            'status' => fake()->regexify('[A-Za-z0-9]{30}'),
        ];
    }
}
