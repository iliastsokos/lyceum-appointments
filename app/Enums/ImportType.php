<?php

namespace App\Enums;

enum ImportType: string
{
    case Teachers = 'teachers';
    case Guardians = 'guardians';

    public function label(): string
    {
        return match ($this) {
            self::Teachers => 'Teachers',
            self::Guardians => 'Guardians',
        };
    }
}
