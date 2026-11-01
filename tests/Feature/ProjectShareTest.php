<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectShareTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create(['name' => 'Summer collection']);

        $item = Item::factory()->for($this->project)->create([
            'name' => 'Resort dress',
            'profit_margin' => 50,
            'quantity_medium' => 4,
            'quantity_large' => 6,
        ]);
        $item->materials()->attach(Material::factory()->for($this->user)->create(['unit_price' => 100]), ['quantity' => 2]);
        $item->costs()->create(['name' => 'Labour', 'amount' => 100]);
    }

    public function test_the_owner_can_create_a_share_link()
    {
        $this->actingAs($this->user)
            ->post(route('projects.share.store', $this->project))
            ->assertRedirect(route('projects.show', $this->project));

        $token = $this->project->fresh()->share_token;
        $this->assertSame(48, strlen((string) $token));

        $this->actingAs($this->user)
            ->get(route('projects.show', $this->project))
            ->assertInertia(fn (Assert $page) => $page->where('shareUrl', route('shared.projects.show', $token)));
    }

    public function test_anyone_with_the_link_sees_the_full_costing_without_logging_in()
    {
        $this->project->share();

        $this->get(route('shared.projects.show', $this->project->share_token))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page
                ->component('shared/project')
                ->where('project.name', 'Summer collection')
                ->where('totalPieces', 10)
                ->where('totalCost', 3000)
                ->where('items.0.name', 'Resort dress')
                ->where('items.0.quantities', ['small' => 0, 'medium' => 4, 'large' => 6, 'xl' => 0])
                ->where('items.0.cost.total_cost', 300)
                ->where('items.0.all_pieces.selling_price', 4500)
                ->where('items.0.all_pieces.profit', 1500)
                ->missing('project.id')
                ->missing('project.share_token'));
    }

    public function test_a_new_link_replaces_the_old_one_and_stopping_disables_it()
    {
        $this->project->share();
        $old = $this->project->share_token;

        $this->actingAs($this->user)->post(route('projects.share.store', $this->project));
        $new = $this->project->fresh()->share_token;

        $this->assertNotSame($old, $new);
        $this->get(route('shared.projects.show', $old))->assertNotFound();
        $this->get(route('shared.projects.show', $new))->assertOk();

        $this->actingAs($this->user)
            ->delete(route('projects.share.destroy', $this->project))
            ->assertRedirect(route('projects.show', $this->project));

        $this->assertNull($this->project->fresh()->share_token);
        $this->get(route('shared.projects.show', $new))->assertNotFound();
    }

    public function test_unknown_links_are_not_found()
    {
        $this->get('/share/'.str_repeat('a', 48))->assertNotFound();
        $this->get('/share/too-short')->assertNotFound();
    }

    public function test_other_users_cannot_share_or_unshare_your_project()
    {
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->post(route('projects.share.store', $this->project))->assertForbidden();
        $this->actingAs($intruder)->delete(route('projects.share.destroy', $this->project))->assertForbidden();
        $this->assertNull($this->project->fresh()->share_token);
    }

    public function test_project_and_report_totals_count_every_piece()
    {
        $this->actingAs($this->user)
            ->get(route('projects.show', $this->project))
            ->assertInertia(fn (Assert $page) => $page
                ->where('totalCost', 3000)
                ->where('items.0.quantity', 10)
                ->where('items.0.all_pieces.total_cost', 3000));

        $this->actingAs($this->user)
            ->get(route('reports.index', ['project_id' => $this->project->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.total_cost', 3000)
                ->where('categories', [
                    ['name' => 'Materials', 'amount' => 2000],
                    ['name' => 'Labour', 'amount' => 1000],
                ])
                ->where('materials.0.quantity', 20));
    }
}
