<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Database\Seeders\MultiYearDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiYearDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_a_project_with_items_saved_across_three_years()
    {
        $user = User::factory()->create();

        $this->seed(MultiYearDemoSeeder::class);
        $this->seed(MultiYearDemoSeeder::class);

        $this->assertSame(1, $user->workspaces()->where('name', MultiYearDemoSeeder::WORKSPACE)->count());

        $years = Item::whereIn('project_id', $user->projects()->pluck('id'))->get()
            ->groupBy(fn (Item $item) => $item->created_at->format('Y'))
            ->map->count()
            ->all();

        $this->assertSame(['2024' => 3, '2025' => 3, '2026' => 3], $years);
    }
}
