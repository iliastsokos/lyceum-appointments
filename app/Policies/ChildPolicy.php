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
     *
     * Admin-only — guardians can no longer edit their own children either
     * (spec change, same reasoning as create()).
     */
    public function update(User $user, Child $child): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Admin-only, for the same reason as update().
     */
    public function delete(User $user, Child $child): bool
    {
        return $user->isAdmin();
    }
}
