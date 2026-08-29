<?php

namespace App\Policies;

use App\Models\AppointmentSlot;
use App\Models\User;

class AppointmentSlotPolicy
{
    public function view(User $user, AppointmentSlot $slot): bool
    {
        return $user->isAdmin() || $slot->teacher_id === $user->id;
    }

    public function update(User $user, AppointmentSlot $slot): bool
    {
        return $user->isAdmin() || $slot->teacher_id === $user->id;
    }
}
