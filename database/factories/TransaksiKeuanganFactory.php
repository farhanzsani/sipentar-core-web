<?php

namespace Database\Factories;

use App\Models\Panens;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiKeuanganFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => Users::factory(),
            'panen_id' => Panens::factory(),
            'jenis' => fake()->regexify('[A-Za-z0-9]{30}'),
            'tanggal' => fake()->date(),
            'jumlah' => fake()->randomFloat(2, 0, 999999999999.99),
            'persentase_kas' => fake()->randomFloat(2, 0, 999.99),
            'kas_operasional' => fake()->randomFloat(2, 0, 999999999999.99),
            'laba_bersih' => fake()->randomFloat(2, 0, 999999999999.99),
            'keterangan' => fake()->regexify('[A-Za-z0-9]{255}'),
        ];
    }
}
