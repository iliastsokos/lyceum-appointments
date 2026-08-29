<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only administrators manage other users (teachers/guardians) accounts.
     * Administrators cannot manage other administrator accounts through
     * this policy — that is a deliberate, small extra safety margin since
     * there is no UI path to create a second admin account.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isAdmin();
    }
}
