<?php

namespace Database\Factories;

use App\Models\MejaTanams;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudidayaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => Users::factory(),
            'meja_id' => MejaTanams::factory(),
            'tanggal_semai' => fake()->date(),
            'jumlah_tanaman' => fake()->numberBetween(-10000, 10000),
            'status' => fake()->regexify('[A-Za-z0-9]{30}'),
        ];
    }
}
