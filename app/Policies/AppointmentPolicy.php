<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin()
            || $appointment->guardian_id === $user->id
            || $appointment->teacher_id === $user->id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $appointment->guardian_id === $user->id;
    }
}
