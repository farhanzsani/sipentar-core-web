<?php

namespace Database\Factories;

use App\Models\MejaTanam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudidayaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'meja_id' => MejaTanam::factory(),
            'tanggal_semai' => fake()->dateTimeBetween('-30 days', 'now'),
            'jumlah_tanaman' => fake()->numberBetween(10, 100),
            'status' => fake()->randomElement(['berjalan', 'selesai']),
        ];
    }
}
