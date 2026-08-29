<?php

namespace App\Enums;

enum SlotStatus: string
{
    case Available = 'available';
    case Booked = 'booked';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Διαθέσιμο',
            self::Booked => 'Κλεισμένο',
            self::Disabled => 'Απενεργοποιημένο',
        };
    }
}
