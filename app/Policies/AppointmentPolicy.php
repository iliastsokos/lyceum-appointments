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

    /**
     * The guardian who booked it, the teacher it was booked with, or an
     * admin acting on the teacher's behalf.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin()
            || $appointment->guardian_id === $user->id
            || $appointment->teacher_id === $user->id;
    }
}
