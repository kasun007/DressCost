<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

/**
 * Workspace access also covers adding projects to it.
 */
class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->user_id === $user->id;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->user_id === $user->id;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->user_id === $user->id;
    }
}
