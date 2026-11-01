<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The report looks at one level at a time: workspaces, then a workspace's
 * projects, then a project's items.
 */
class ReportLevelsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $fashion;

    private Workspace $bespoke;

    private Project $avurudu;

    private Project $summer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->fashion = Workspace::factory()->for($this->user)->create(['name' => '2026 Fashion']);
        $this->bespoke = Workspace::factory()->for($this->user)->create(['name' => 'Bespoke orders']);
        $this->avurudu = Project::factory()->inWorkspace($this->fashion)->create(['name' => 'Avurudu']);
        $this->summer = Project::factory()->inWorkspace($this->fashion)->create(['name' => 'Summer collection']);
        $wedding = Project::factory()->inWorkspace($this->bespoke)->create(['name' => 'Wedding']);

        $this->item($this->avurudu, '2026-04-02', 'Labour', 100);
        $this->item($this->summer, '2026-10-05', 'Labour', 300);
        $this->item($this->summer, '2026-10-20', 'Beading', 200);
        $this->item($wedding, '2026-06-10', 'Labour', 50);
    }

    public function test_with_no_focus_the_report_is_split_by_workspace()
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('scope', null)
                ->has('tree', 2)
                ->where('tree.0.kind', 'workspace')
                ->where('tree.0.name', '2026 Fashion')
                ->where('tree.0.total_cost', 600)
                ->where('tree.0.items_count', 3)
                ->where('tree.0.children.1.name', 'Summer collection')
                ->where('tree.0.children.1.items.0.total_cost', 200)
                ->where('tree.1.name', 'Bespoke orders')
                ->where('options.projects.1.name', '2026 Fashion › Summer collection')
                ->where('options.projects.1.short_name', 'Summer collection'));
    }

    public function test_focusing_on_a_workspace_splits_it_by_project()
    {
        $this->actingAs($this->user)
            ->get(route('reports.index', ['workspace_id' => $this->fashion->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('scope.kind', 'workspace')
                ->where('scope.path', [['kind' => 'workspace', 'id' => $this->fashion->id, 'name' => '2026 Fashion']])
                ->where('summary.total_cost', 600)
                ->where('tree.0.kind', 'project')
                ->where('tree.0.name', 'Avurudu')
                ->where('tree.1.total_cost', 500)
                ->where('years', [['year' => '2026', 'items_count' => 3, 'total_cost' => 600]]));
    }

    public function test_focusing_on_a_project_shows_its_items_and_full_path()
    {
        $this->actingAs($this->user)
            ->get(route('reports.index', ['project_id' => $this->summer->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('scope.kind', 'project')
                ->where('scope.path.0.name', '2026 Fashion')
                ->where('scope.path.1.name', 'Summer collection')
                ->has('tree', 1)
                ->has('tree.0.items', 2)
                ->where('summary.total_cost', 500));
    }

    private function item(Project $project, string $savedOn, string $costType, float $amount): void
    {
        Item::factory()->for($project)->create(['created_at' => $savedOn])
            ->costs()->create(['name' => $costType, 'amount' => $amount]);
    }
}
