<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
    }

    public function test_the_create_form_offers_saved_items_once_per_name_with_their_latest_costing()
    {
        $fabric = Material::factory()->for($this->user)->create();
        $old = Item::factory()->for($this->project)->create(['name' => 'A-line dress', 'created_at' => '2026-01-01 09:00:00']);
        $old->costs()->create(['name' => 'Labour', 'amount' => 100]);
        $latest = Item::factory()->for($this->project)->create(['name' => 'a-line DRESS', 'created_at' => '2026-05-01 09:00:00', 'profit_margin' => 30]);
        $latest->materials()->attach($fabric, ['quantity' => 2.5]);
        $latest->costs()->create(['name' => 'Labour', 'amount' => 150]);
        Item::factory()->create(['name' => 'Someone else’s dress']);

        $this->actingAs($this->user)
            ->get(route('projects.items.create', $this->project))
            ->assertInertia(fn (Assert $page) => $page
                ->has('savedItems', 1)
                ->where('savedItems.0.id', $latest->id)
                ->where('savedItems.0.profit_margin', 30)
                ->where('savedItems.0.materials', [['material_id' => $fabric->id, 'quantity' => 2.5]])
                ->where('savedItems.0.costs', [['name' => 'Labour', 'amount' => 150]]));
    }

    public function test_the_create_form_lists_the_users_materials()
    {
        Material::factory()->for($this->user)->create();
        Material::factory()->create();

        $this->actingAs($this->user)
            ->get(route('projects.items.create', $this->project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('items/create')
                ->has('materials', 1)
                ->has('types'));
    }

    public function test_an_item_can_be_added_with_materials()
    {
        $fabric = Material::factory()->for($this->user)->create();
        $zip = Material::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->post(route('projects.items.store', $this->project), [
                'name' => 'A-line dress',
                'type' => 'dress',
                'profit_margin' => 25,
                'quantity_small' => 10,
                'quantity_medium' => 20,
                'quantity_large' => 15,
                'quantity_xl' => 5,
                'materials' => [
                    ['material_id' => $fabric->id, 'quantity' => 2.5],
                    ['material_id' => $zip->id, 'quantity' => 1],
                ],
                'costs' => [
                    ['name' => 'Labour', 'amount' => 1500],
                    ['name' => 'Hand embroidery', 'amount' => 800],
                ],
            ])
            ->assertRedirect(route('projects.show', $this->project));

        $item = $this->project->items()->firstOrFail();
        $this->assertSame('A-line dress', $item->name);
        $this->assertSame(['small' => 10, 'medium' => 20, 'large' => 15, 'xl' => 5], $item->quantities());
        $this->assertSame(50, $item->quantity());
        $this->assertTrue($item->created_at->isToday());
        $this->assertSame(2.5, $item->materials->firstWhere('id', $fabric->id)?->pivot->quantity);
        $this->assertCount(2, $item->materials);
        $this->assertSame(
            ['Labour' => 1500.0, 'Hand embroidery' => 800.0],
            $item->costs->pluck('amount', 'name')->all(),
        );
    }

    public function test_an_item_cannot_use_another_users_material()
    {
        $foreign = Material::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.items.store', $this->project), [
                'name' => 'Dress',
                'type' => 'dress',
                'profit_margin' => 0,
                'materials' => [['material_id' => $foreign->id, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('materials.0.material_id');
    }

    public function test_the_same_material_cannot_be_listed_twice()
    {
        $fabric = Material::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->post(route('projects.items.store', $this->project), [
                'name' => 'Dress',
                'type' => 'dress',
                'profit_margin' => 0,
                'materials' => [
                    ['material_id' => $fabric->id, 'quantity' => 1],
                    ['material_id' => $fabric->id, 'quantity' => 2],
                ],
            ])
            ->assertSessionHasErrors('materials.0.material_id');
    }

    public function test_an_item_needs_at_least_one_piece()
    {
        $this->actingAs($this->user)
            ->post(route('projects.items.store', $this->project), [
                'name' => 'Dress',
                'type' => 'dress',
                'profit_margin' => 0,
                'quantity_small' => 0,
                'quantity_medium' => 0,
                'quantity_large' => 0,
                'quantity_xl' => -1,
            ])
            ->assertSessionHasErrors(['quantity', 'quantity_xl']);
    }

    public function test_a_costing_type_can_only_appear_once_per_item()
    {
        $this->actingAs($this->user)
            ->post(route('projects.items.store', $this->project), [
                'name' => 'Dress',
                'type' => 'dress',
                'profit_margin' => 0,
                'costs' => [
                    ['name' => 'Labour', 'amount' => 10],
                    ['name' => 'labour', 'amount' => 20],
                ],
            ])
            ->assertSessionHasErrors('costs.0.name');
    }

    public function test_a_cost_line_needs_a_type_and_a_valid_amount()
    {
        $this->actingAs($this->user)
            ->post(route('projects.items.store', $this->project), [
                'name' => 'Dress',
                'type' => 'dress',
                'profit_margin' => 0,
                'costs' => [['name' => '', 'amount' => -5]],
            ])
            ->assertSessionHasErrors(['costs.0.name', 'costs.0.amount']);
    }

    public function test_costing_types_include_defaults_and_the_users_custom_types()
    {
        Item::factory()->for($this->project)->create()
            ->costs()->createMany([
                ['name' => 'Embroidery', 'amount' => 1],
                ['name' => 'labour', 'amount' => 1],
            ]);
        Item::factory()->create()->costs()->create(['name' => 'Someone else’s type', 'amount' => 1]);

        $this->actingAs($this->user)
            ->get(route('projects.items.create', $this->project))
            ->assertInertia(fn (Assert $page) => $page
                ->where('costTypes', ['Labour', 'Overhead', 'Embroidery']));
    }

    public function test_updating_an_item_replaces_its_materials_and_costs()
    {
        $old = Material::factory()->for($this->user)->create();
        $new = Material::factory()->for($this->user)->create();
        $item = Item::factory()->for($this->project)->create();
        $item->materials()->attach($old, ['quantity' => 1]);
        $item->costs()->create(['name' => 'Labour', 'amount' => 100]);

        $this->actingAs($this->user)
            ->put(route('projects.items.update', [$this->project, $item]), [
                'name' => 'Updated',
                'type' => 'skirt',
                'quantity_small' => 0,
                'quantity_medium' => 0,
                'quantity_large' => 3,
                'quantity_xl' => 0,
                'profit_margin' => 0,
                'materials' => [['material_id' => $new->id, 'quantity' => 3]],
                'costs' => [['name' => 'Beading', 'amount' => 40]],
            ])
            ->assertRedirect(route('projects.show', $this->project));

        $item->refresh();
        $this->assertSame('Updated', $item->name);
        $this->assertSame([$new->id], $item->materials->pluck('id')->all());
        $this->assertSame(['Beading' => 40.0], $item->costs->pluck('amount', 'name')->all());
    }

    public function test_the_edit_form_includes_the_items_materials_and_costs()
    {
        $fabric = Material::factory()->for($this->user)->create();
        $item = Item::factory()->for($this->project)->create();
        $item->materials()->attach($fabric, ['quantity' => 1.5]);
        $item->costs()->create(['name' => 'Labour', 'amount' => 300]);

        $this->actingAs($this->user)
            ->get(route('projects.items.edit', [$this->project, $item]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('items/edit')
                ->where('item.costs.0.name', 'Labour')
                ->where('item.costs.0.amount', 300)
                ->where('item.materials.0.material_id', $fabric->id)
                ->where('item.materials.0.quantity', 1.5));
    }

    public function test_an_item_can_be_deleted()
    {
        $item = Item::factory()->for($this->project)->create();

        $this->actingAs($this->user)
            ->delete(route('projects.items.destroy', [$this->project, $item]))
            ->assertRedirect(route('projects.show', $this->project));

        $this->assertModelMissing($item);
    }

    public function test_users_cannot_touch_items_in_another_users_project()
    {
        $item = Item::factory()->for($this->project)->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('projects.items.edit', [$this->project, $item]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('projects.items.destroy', [$this->project, $item]))
            ->assertForbidden();
    }

    public function test_an_item_must_belong_to_the_project_in_the_url()
    {
        $otherProject = Project::factory()->for($this->user)->create();
        $item = Item::factory()->for($otherProject)->create();

        $this->actingAs($this->user)
            ->get(route('projects.items.edit', [$this->project, $item]))
            ->assertNotFound();
    }
}
