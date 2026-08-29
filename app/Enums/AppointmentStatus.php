<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Νέο',
            self::Confirmed => 'Επιβεβαιωμένο',
            self::Cancelled => 'Ακυρωμένο',
            self::Completed => 'Ολοκληρωμένο',
        };
    }
}
