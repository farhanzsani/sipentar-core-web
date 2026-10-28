<?php

namespace Database\Factories;

use App\Models\Budidayas;
use App\Models\Users;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosisFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => Users::factory(),
            'budidaya_id' => Budidayas::factory(),
            'image_path' => fake()->regexify('[A-Za-z0-9]{255}'),
            'hasil' => fake()->regexify('[A-Za-z0-9]{30}'),
            'confidence' => fake()->randomFloat(2, 0, 999.99),
            'rekomendasi' => fake()->text(),
            'tanggal_diagnosis' => fake()->dateTime(),
        ];
    }
}
