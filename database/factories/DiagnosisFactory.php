<?php

namespace Database\Factories;

use App\Models\Budidaya;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosisFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $hasilOptions = ['sehat', 'busuk_batang', 'cacar_daun', 'busuk_akar'];
        $hasil = fake()->randomElement($hasilOptions);

        return [
            'user_id' => User::factory(),
            'budidaya_id' => Budidaya::factory(),
            'image_path' => 'diagnosis/' . fake()->uuid() . '.jpg',
            'hasil' => $hasil,
            'confidence' => fake()->randomFloat(2, 70.00, 99.99),
            'rekomendasi' => config("sipentar.diagnosis_recommendations.{$hasil}", 'Pemeriksaan selesai.'),
            'tanggal_diagnosis' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
