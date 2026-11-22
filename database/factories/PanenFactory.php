<?php

namespace Database\Factories;

use App\Models\Budidaya;
use Illuminate\Database\Eloquent\Factories\Factory;

class PanenFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'budidaya_id' => Budidaya::factory(),
            'tanggal_panen' => fake()->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'berat_kg' => fake()->randomFloat(2, 5, 50),
            'status' => fake()->randomElement(['terjadwal', 'selesai']),
        ];
    }
}
