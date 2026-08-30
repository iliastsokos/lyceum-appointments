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
     * Admin-only: guardians can no longer add their own children (spec
     * change) — new children are added by an admin, from the guardian's
     * edit page or via bulk Excel import.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
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
