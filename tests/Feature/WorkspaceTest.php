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

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_workspaces()
    {
        $this->get(route('workspaces.index'))->assertRedirect(route('login'));
    }

    public function test_users_only_see_their_own_workspaces_with_counts_but_no_costs()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->for($user)->create(['name' => '2026 Fashion']);
        Project::factory()->inWorkspace($workspace)->count(2)->create();
        Item::factory()->for(Project::factory()->inWorkspace($workspace)->create())->create()
            ->costs()->create(['name' => 'Labour', 'amount' => 120]);
        Workspace::factory()->create();

        $this->actingAs($user)
            ->get(route('workspaces.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('workspaces/index')
                ->has('workspaces', 1)
                ->where('workspaces.0.name', '2026 Fashion')
                ->where('workspaces.0.projects_count', 3)
                ->where('workspaces.0.items_count', 1)
                ->missing('workspaces.0.cost'));
    }

    public function test_a_workspace_can_be_created_and_updated()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('workspaces.store'), ['name' => '2026 Fashion'])->assertSessionHasNoErrors();
        $workspace = Workspace::firstOrFail();
        $this->assertSame($user->id, $workspace->user_id);

        $this->actingAs($user)
            ->put(route('workspaces.update', $workspace), ['name' => '2027 Fashion'])
            ->assertRedirect(route('workspaces.show', $workspace));
        $this->assertSame('2027 Fashion', $workspace->fresh()->name);
    }

    public function test_a_workspace_page_lists_its_projects()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->for($user)->create();
        Project::factory()->inWorkspace($workspace)->create(['name' => 'Summer collection']);

        $this->actingAs($user)
            ->get(route('workspaces.show', $workspace))
            ->assertInertia(fn (Assert $page) => $page
                ->component('workspaces/show')
                ->has('projects', 1)
                ->where('projects.0.name', 'Summer collection')
                ->where('projects.0.total_cost', 0)
                ->missing('projects.0.selling_price')
                ->missing('total'));
    }

    public function test_users_cannot_access_another_users_workspace()
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('workspaces.show', $workspace))->assertForbidden();
        $this->actingAs($user)->put(route('workspaces.update', $workspace), ['name' => 'x'])->assertForbidden();
        $this->actingAs($user)->delete(route('workspaces.destroy', $workspace))->assertForbidden();
    }

    public function test_deleting_a_workspace_deletes_its_projects_and_items()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->for($user)->create();
        $project = Project::factory()->inWorkspace($workspace)->create();
        $item = Item::factory()->for($project)->create();
        $item->materials()->attach(Material::factory()->for($user)->create(), ['quantity' => 1]);
        $item->costs()->create(['name' => 'Labour', 'amount' => 10]);

        $this->actingAs($user)
            ->delete(route('workspaces.destroy', $workspace))
            ->assertRedirect(route('workspaces.index'));

        $this->assertModelMissing($workspace);
        $this->assertModelMissing($project);
        $this->assertModelMissing($item);
        $this->assertDatabaseCount('item_material', 0);
        $this->assertDatabaseCount('item_costs', 0);
    }

    public function test_an_account_with_materials_in_use_can_be_deleted()
    {
        $user = User::factory()->create();
        $material = Material::factory()->for($user)->create();
        Item::factory()->for(Project::factory()->for($user)->create())->create()
            ->materials()->attach($material, ['quantity' => 2]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertModelMissing($user);
        $this->assertModelMissing($material);
        $this->assertDatabaseCount('items', 0);
        $this->assertDatabaseCount('workspaces', 0);
    }
}
