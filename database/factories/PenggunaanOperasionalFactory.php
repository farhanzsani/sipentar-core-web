<?php

namespace Database\Factories;

use App\Models\Stoks;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenggunaanOperasionalFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => Users::factory(),
            'stok_id' => Stoks::factory(),
            'tanggal' => fake()->date(),
            'jumlah' => fake()->randomFloat(2, 0, 99999999.99),
            'keterangan' => fake()->regexify('[A-Za-z0-9]{255}'),
        ];
    }
}
