<?php

namespace App\Policies;

use App\Models\Availability;
use App\Models\User;

class AvailabilityPolicy
{
    public function view(User $user, Availability $availability): bool
    {
        return $user->isAdmin() || $availability->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function delete(User $user, Availability $availability): bool
    {
        return $user->isAdmin() || $availability->teacher_id === $user->id;
    }
}
