<?php

namespace App\Policies;

use App\Models\Child;
use App\Models\User;

class ChildPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Child $child): bool
    {
        return $user->isAdmin() || $child->guardian_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     *
     * Guardians may create children for their own account; admins may
     * create a child for any guardian via the admin panel.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isGuardian();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Child $child): bool
    {
        return $user->isAdmin() || $child->guardian_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Child $child): bool
    {
        return $user->isAdmin() || $child->guardian_id === $user->id;
    }
}
