<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Items belong to a project, so project access also covers its items.
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }
}
