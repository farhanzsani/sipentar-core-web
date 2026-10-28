<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->regexify('[A-Za-z0-9]{100}'),
            'username' => fake()->userName(),
            'password' => fake()->password(),
            'role' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
