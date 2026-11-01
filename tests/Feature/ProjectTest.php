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

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->for($this->user)->create(['name' => '2026 Fashion']);
    }

    public function test_a_project_can_be_created_in_a_workspace()
    {
        $response = $this->actingAs($this->user)->post(route('projects.store'), [
            'workspace_id' => $this->workspace->id,
            'name' => 'Summer collection',
        ]);

        $project = Project::firstOrFail();
        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame($this->workspace->id, $project->workspace_id);
        $this->assertSame($this->user->id, $project->user_id);
    }

    public function test_a_project_needs_a_name_and_one_of_your_workspaces()
    {
        $this->actingAs($this->user)
            ->post(route('projects.store'), ['name' => '', 'workspace_id' => Workspace::factory()->create()->id])
            ->assertSessionHasErrors(['name', 'workspace_id']);
    }

    public function test_a_project_shows_its_workspace_items_and_total_cost()
    {
        $project = Project::factory()->inWorkspace($this->workspace)->create();
        $material = Material::factory()->for($this->user)->create(['unit_price' => 100]);
        $item = Item::factory()->for($project)->create(['profit_margin' => 10]);
        $item->materials()->attach($material, ['quantity' => 2]);
        $item->costs()->create(['name' => 'Labour', 'amount' => 50]);

        $this->actingAs($this->user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/show')
                ->where('workspace.name', '2026 Fashion')
                ->has('items', 1)
                ->where('items.0.cost.other_cost', 50)
                ->where('items.0.cost.total_cost', 250)
                ->where('items.0.cost.selling_price', 275)
                ->where('items.0.cost.profit', 25)
                ->where('totalCost', 250)
                ->missing('total'));
    }

    public function test_a_project_can_be_moved_to_another_workspace()
    {
        $project = Project::factory()->inWorkspace($this->workspace)->create();
        $other = Workspace::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), ['name' => 'Renamed', 'workspace_id' => $other->id])
            ->assertRedirect(route('projects.show', $project));

        $project->refresh();
        $this->assertSame('Renamed', $project->name);
        $this->assertSame($other->id, $project->workspace_id);
    }

    public function test_users_cannot_access_another_users_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)->get(route('projects.show', $project))->assertForbidden();
        $this->actingAs($this->user)
            ->put(route('projects.update', $project), ['name' => 'x', 'workspace_id' => $this->workspace->id])
            ->assertForbidden();
        $this->actingAs($this->user)->delete(route('projects.destroy', $project))->assertForbidden();
    }

    public function test_deleting_a_project_deletes_its_items_and_returns_to_the_workspace()
    {
        $project = Project::factory()->inWorkspace($this->workspace)->create();
        $item = Item::factory()->for($project)->create();
        $item->costs()->create(['name' => 'Labour', 'amount' => 10]);

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('workspaces.show', $this->workspace));

        $this->assertModelMissing($project);
        $this->assertModelMissing($item);
        $this->assertDatabaseCount('item_costs', 0);
    }
}
