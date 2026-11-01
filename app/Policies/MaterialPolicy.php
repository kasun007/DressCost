<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function update(User $user, Material $material): bool
    {
        return $material->user_id === $user->id;
    }

    public function delete(User $user, Material $material): bool
    {
        return $material->user_id === $user->id;
    }
}
