<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_materials()
    {
        $this->get(route('materials.index'))->assertRedirect(route('login'));
    }

    public function test_users_only_see_their_own_materials()
    {
        $user = User::factory()->create();
        Material::factory()->for($user)->create(['name' => 'Silk']);
        Material::factory()->create(['name' => 'Someone else’s linen']);

        $this->actingAs($user)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('materials/index')
                ->has('materials', 1)
                ->where('materials.0.name', 'Silk')
                ->has('units'));
    }

    public function test_a_material_can_be_added()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'name' => 'Cotton poplin',
                'unit' => 'metre',
                'unit_price' => '850.50',
            ])
            ->assertRedirect(route('materials.index'));

        $this->assertDatabaseHas('materials', [
            'user_id' => $user->id,
            'name' => 'Cotton poplin',
            'unit' => 'metre',
            'unit_price' => 850.50,
        ]);
    }

    public function test_material_input_is_validated()
    {
        $user = User::factory()->create();
        Material::factory()->for($user)->create(['name' => 'Silk']);

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'name' => 'Silk',
                'unit' => 'bucket',
                'unit_price' => -1,
            ])
            ->assertSessionHasErrors(['name', 'unit', 'unit_price']);
    }

    public function test_a_material_can_be_updated()
    {
        $user = User::factory()->create();
        $material = Material::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('materials.update', $material), [
                'name' => $material->name,
                'unit' => 'yard',
                'unit_price' => 99,
            ])
            ->assertRedirect(route('materials.index'));

        $this->assertSame(99.0, $material->fresh()->unit_price);
    }

    public function test_users_cannot_change_another_users_material()
    {
        $material = Material::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('materials.update', $material), [
                'name' => 'Stolen',
                'unit' => 'metre',
                'unit_price' => 1,
            ])
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->delete(route('materials.destroy', $material))
            ->assertForbidden();
    }

    public function test_an_unused_material_can_be_deleted()
    {
        $user = User::factory()->create();
        $material = Material::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('materials.destroy', $material))
            ->assertRedirect(route('materials.index'));

        $this->assertModelMissing($material);
    }

    public function test_a_material_used_by_an_item_cannot_be_deleted()
    {
        $user = User::factory()->create();
        $material = Material::factory()->for($user)->create();
        $item = Item::factory()->create();
        $item->materials()->attach($material, ['quantity' => 1]);

        $this->actingAs($user)
            ->delete(route('materials.destroy', $material))
            ->assertRedirect(route('materials.index'));

        $this->assertModelExists($material);
    }
}
