<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompareTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $fashion;

    private Workspace $bespoke;

    private Project $summer;

    private Project $wedding;

    private Item $gown;

    private Item $shirt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->fashion = Workspace::factory()->for($this->user)->create(['name' => '2026 Fashion']);
        $this->bespoke = Workspace::factory()->for($this->user)->create(['name' => 'Bespoke orders']);
        $this->summer = Project::factory()->inWorkspace($this->fashion)->create(['name' => 'Summer']);
        $this->wedding = Project::factory()->inWorkspace($this->bespoke)->create(['name' => 'Wedding']);

        $this->shirt = Item::factory()->for($this->summer)->create(['name' => 'Linen shirt', 'created_at' => '2026-05-01 10:00:00']);
        $this->shirt->costs()->createMany([
            ['name' => 'Labour', 'amount' => 100],
            ['name' => 'Overhead', 'amount' => 20],
        ]);

        $this->gown = Item::factory()->for($this->wedding)->create(['name' => 'Bridal gown', 'created_at' => '2026-06-01 10:00:00']);
        $this->gown->costs()->createMany([
            ['name' => 'Labour', 'amount' => 300],
            ['name' => 'Beading', 'amount' => 200],
        ]);
    }

    public function test_two_projects_can_be_compared()
    {
        $this->actingAs($this->user)
            ->get(route('reports.compare', ['kind' => 'project', 'a' => $this->summer->id, 'b' => $this->wedding->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/compare')
                ->where('kind', 'project')
                ->where('a.name', 'Summer')
                ->where('a.detail', '2026 Fashion')
                ->where('a.total_cost', 120)
                ->where('b.name', 'Wedding')
                ->where('b.total_cost', 500)
                ->where('b.categories', [
                    ['name' => 'Labour', 'amount' => 300],
                    ['name' => 'Beading', 'amount' => 200],
                ]));
    }

    public function test_two_workspaces_can_be_compared()
    {
        Item::factory()->for(Project::factory()->inWorkspace($this->fashion)->create())->create()
            ->costs()->create(['name' => 'Labour', 'amount' => 80]);

        $this->actingAs($this->user)
            ->get(route('reports.compare', ['kind' => 'workspace', 'a' => $this->fashion->id, 'b' => $this->bespoke->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('a.name', '2026 Fashion')
                ->where('a.items_count', 2)
                ->where('a.total_cost', 200)
                ->where('b.total_cost', 500));
    }

    public function test_two_items_can_be_compared()
    {
        $this->actingAs($this->user)
            ->get(route('reports.compare', ['kind' => 'item', 'a' => $this->shirt->id, 'b' => $this->gown->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('a.name', 'Linen shirt')
                ->where('a.detail', '2026 Fashion › Summer, saved 1 May 2026')
                ->where('options.0.name', 'Linen shirt (2026 Fashion › Summer, 1 May 2026)')
                ->where('a.total_cost', 120)
                ->where('b.total_cost', 500)
                ->has('options', 2));
    }

    public function test_the_first_two_choices_are_compared_by_default()
    {
        $this->actingAs($this->user)
            ->get(route('reports.compare', ['kind' => 'workspace']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('options', 2)
                ->where('a.name', '2026 Fashion')
                ->where('b.name', 'Bespoke orders'));
    }

    public function test_only_your_own_data_can_be_compared()
    {
        $foreign = Item::factory()->create();

        $this->actingAs($this->user)
            ->get(route('reports.compare', ['kind' => 'item', 'a' => $this->shirt->id, 'b' => $foreign->id]))
            ->assertSessionHasErrors('b');

        $this->actingAs($this->user)
            ->get(route('reports.compare', ['kind' => 'galaxy']))
            ->assertSessionHasErrors('kind');
    }
}
