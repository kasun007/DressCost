<?php

namespace Database\Factories;

use App\Enums\MaterialUnit;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'unit' => fake()->randomElement(MaterialUnit::cases()),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
        ];
    }
}
