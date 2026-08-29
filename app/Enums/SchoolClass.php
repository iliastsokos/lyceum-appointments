<?php

namespace App\Enums;

/**
 * The fixed set of classes at a Greek Lyceum: grades Α (1st), Β (2nd),
 * Γ (3rd), each with sections 1-3 — transliterated here as A/B/G so plain
 * ASCII can be typed/imported without encoding issues.
 */
enum SchoolClass: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case A3 = 'A3';
    case B1 = 'B1';
    case B2 = 'B2';
    case B3 = 'B3';
    case G1 = 'G1';
    case G2 = 'G2';
    case G3 = 'G3';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
