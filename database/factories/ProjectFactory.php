<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state. The workspace belongs to the same user.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => fn (array $attributes) => Workspace::factory()->create(['user_id' => $attributes['user_id']]),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Put the project in a workspace, owned by the workspace's user.
     */
    public function inWorkspace(Workspace $workspace): static
    {
        return $this->state(fn () => [
            'user_id' => $workspace->user_id,
            'workspace_id' => $workspace->id,
        ]);
    }
}
