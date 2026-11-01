<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $summer;

    private Project $wedding;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $silk = Material::factory()->for($this->user)->create(['name' => 'Silk', 'unit_price' => 100]);

        $workspace = Workspace::factory()->for($this->user)->create(['name' => '2026 Fashion']);
        $this->summer = Project::factory()->inWorkspace($workspace)->create(['name' => 'Summer']);
        $this->wedding = Project::factory()->inWorkspace($workspace)->create(['name' => 'Wedding']);

        // Summer, January: materials 200 + labour 50 = 250, no margin.
        $dress = Item::factory()->for($this->summer)->create(['created_at' => '2026-01-10 12:00:00', 'profit_margin' => 0]);
        $dress->materials()->attach($silk, ['quantity' => 2]);
        $dress->costs()->create(['name' => 'Labour', 'amount' => 50]);

        // Wedding, March: materials 100 + labour 300 + beading 100 = 500, 50% margin.
        $gown = Item::factory()->for($this->wedding)->create(['created_at' => '2026-03-20 12:00:00', 'profit_margin' => 50]);
        $gown->materials()->attach($silk, ['quantity' => 1]);
        $gown->costs()->createMany([
            ['name' => 'labour', 'amount' => 300],
            ['name' => 'Beading', 'amount' => 100],
        ]);

        // Another user's data never appears.
        Item::factory()->create()->costs()->create(['name' => 'Labour', 'amount' => 999]);
    }

    public function test_the_report_totals_projects_categories_and_material_usage()
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('summary.projects_count', 2)
                ->where('summary.items_count', 2)
                ->where('summary.total_cost', 750)
                ->where('summary.selling_price', 1000)
                ->where('summary.profit', 250)
                ->where('categories', [
                    ['name' => 'Labour', 'amount' => 350],
                    ['name' => 'Materials', 'amount' => 300],
                    ['name' => 'Beading', 'amount' => 100],
                ])
                ->where('options.categories', ['Materials', 'Labour', 'Overhead', 'Beading'])
                ->has('projects', 2)
                ->where('projects.1.name', '2026 Fashion › Wedding')
                ->where('projects.1.total_cost', 500)
                ->where('materials.0.quantity', 3)
                ->where('materials.0.cost', 300));
    }

    public function test_costs_are_totalled_by_the_year_items_were_saved()
    {
        Item::factory()->for($this->summer)->create(['created_at' => '2025-11-02 09:00:00', 'profit_margin' => 0])
            ->costs()->create(['name' => 'Labour', 'amount' => 80]);

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('trend')
                ->where('years', [
                    ['year' => '2025', 'items_count' => 1, 'total_cost' => 80],
                    ['year' => '2026', 'items_count' => 2, 'total_cost' => 750],
                ]));

        $this->actingAs($this->user)
            ->get(route('reports.index', ['cost_type' => 'Labour']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('years.1', ['year' => '2026', 'items_count' => 2, 'total_cost' => 350]));
    }

    public function test_the_report_can_be_filtered_by_project()
    {
        $this->actingAs($this->user)
            ->get(route('reports.index', ['project_id' => $this->summer->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.project_id', $this->summer->id)
                ->where('summary.total_cost', 250)
                ->has('projects', 1)
                ->where('projects.0.name', '2026 Fashion › Summer'));
    }

    public function test_the_report_can_be_filtered_by_costing_type_ignoring_case()
    {
        $this->actingAs($this->user)
            ->get(route('reports.index', ['cost_type' => 'LABOUR']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.total_cost', 350)
                ->where('summary.selling_price', null)
                ->where('summary.profit', null)
                ->where('categories', [['name' => 'Labour', 'amount' => 350]])
                ->where('materials', []));
    }

    public function test_the_report_can_be_filtered_by_date_range()
    {
        $this->actingAs($this->user)
            ->get(route('reports.index', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.items_count', 1)
                ->where('summary.total_cost', 500)
                ->where('years', [['year' => '2026', 'items_count' => 1, 'total_cost' => 500]]));
    }

    public function test_a_to_date_includes_items_saved_later_that_day()
    {
        Item::factory()->for($this->summer)->create(['created_at' => '2026-03-20 18:45:00'])
            ->costs()->create(['name' => 'Labour', 'amount' => 25]);

        $this->actingAs($this->user)
            ->get(route('reports.index', ['from' => '2026-03-20', 'to' => '2026-03-20']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.items_count', 2)
                ->where('summary.total_cost', 525));
    }

    public function test_filters_are_validated()
    {
        $foreignProject = Project::factory()->create();

        $this->actingAs($this->user)
            ->get(route('reports.index', [
                'project_id' => $foreignProject->id,
                'from' => '2026-05-01',
                'to' => '2026-04-01',
            ]))
            ->assertSessionHasErrors(['project_id', 'to']);
    }

    public function test_the_dashboard_shows_the_total_cost_by_year_and_recent_projects()
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('summary.projects_count', 2)
                ->where('summary.total_cost', 750)
                ->where('years', [['year' => '2026', 'items_count' => 2, 'total_cost' => 750]])
                ->missing('trend')
                ->where('recentProjects.0.name', '2026 Fashion › Wedding'));
    }
}
