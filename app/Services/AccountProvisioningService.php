<?php

namespace App\Services;

use Illuminate\Support\Str;

class AccountProvisioningService
{
    /**
     * Generate a random temporary password for a newly provisioned account
     * (manual admin creation or bulk Excel import). Deliberately short (4
     * characters, matching the app's own 4-character minimum) so it's easy
     * for an admin to read aloud or write down for a parent/teacher — the
     * user must change it on first login regardless. The caller is
     * responsible for hashing it before storage and for displaying it to
     * the administrator exactly once — it must never be logged or persisted
     * in plain text (spec §20, §22).
     */
    public function generateTemporaryPassword(): string
    {
        return Str::password(4, symbols: false);
    }
}
