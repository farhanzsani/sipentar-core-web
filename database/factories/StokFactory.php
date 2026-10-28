<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StokFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama_bahan' => fake()->regexify('[A-Za-z0-9]{100}'),
            'kategori' => fake()->regexify('[A-Za-z0-9]{50}'),
            'satuan' => fake()->regexify('[A-Za-z0-9]{20}'),
            'jumlah_stok' => fake()->randomFloat(2, 0, 99999999.99),
            'batas_minimum' => fake()->randomFloat(2, 0, 99999999.99),
        ];
    }
}
