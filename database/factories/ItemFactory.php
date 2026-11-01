<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            'type' => ItemType::Dress,
            'profit_margin' => fake()->randomFloat(2, 0, 50),
            'quantity_medium' => 1,
        ];
    }
}
