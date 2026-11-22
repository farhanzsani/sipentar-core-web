<?php

namespace Database\Factories;

use App\Models\Panen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiKeuanganFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(['pemasukan', 'pengeluaran']);
        
        return [
            'user_id' => User::factory(),
            'panen_id' => null,
            'jenis' => $jenis,
            'tanggal' => fake()->date(),
            'jumlah' => fake()->randomFloat(2, 10000, 5000000),
            'persentase_kas' => $jenis === 'pemasukan' ? fake()->randomFloat(2, 30, 40) : null,
            'kas_operasional' => null,
            'laba_bersih' => null,
            'keterangan' => fake()->sentence(),
        ];
    }
}
