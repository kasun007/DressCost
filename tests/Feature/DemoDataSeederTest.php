<?php

namespace Tests\Feature;

use App\Costing\CostReport;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_consistent_demo_data()
    {
        $this->seed(DemoDataSeeder::class);

        $user = User::where('email', DemoDataSeeder::EMAIL)->firstOrFail();
        $report = app(CostReport::class)->for($user);

        $this->assertSame(7, $report['summary']['projects_count']);
        $this->assertSame(27, $report['summary']['items_count']);
        $this->assertSame(['2025 Fashion', '2026 Fashion', 'Bespoke orders'], array_column($report['tree'], 'name'));
        $this->assertContains('2026 Fashion › Summer collection', array_column($report['options']['projects'], 'name'));
        // Items are dated by when they were saved, so all demo costs fall in this year.
        $this->assertSame([now()->format('Y')], array_column($report['years'], 'year'));
        $this->assertCount(8, $report['categories']);

        $total = $report['summary']['total_cost'];
        $this->assertEqualsWithDelta($total, collect($report['projects'])->sum('total_cost'), 0.01);
        $this->assertEqualsWithDelta($total, collect($report['categories'])->sum('amount'), 0.01);
        $this->assertEqualsWithDelta($total, collect($report['tree'])->sum('total_cost'), 0.01);
    }

    public function test_reseeding_resets_only_the_demo_account()
    {
        $other = Project::factory()->create();

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $user = User::where('email', DemoDataSeeder::EMAIL)->firstOrFail();

        $this->assertSame(3, $user->workspaces()->count());
        $this->assertSame(7, $user->projects()->count());
        $this->assertSame(13, $user->materials()->count());
        $this->assertModelExists($other);
    }
}
