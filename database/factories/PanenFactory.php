<?php

namespace Database\Factories;

use App\Models\Budidayas;
use Illuminate\Database\Eloquent\Factories\Factory;

class PanenFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'budidaya_id' => Budidayas::factory(),
            'tanggal_panen' => fake()->date(),
            'berat_kg' => fake()->randomFloat(2, 0, 99999999.99),
            'status' => fake()->regexify('[A-Za-z0-9]{30}'),
        ];
    }
}
