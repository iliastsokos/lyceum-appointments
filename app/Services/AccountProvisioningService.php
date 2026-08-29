<?php

namespace App\Services;

use Illuminate\Support\Str;

class AccountProvisioningService
{
    /**
     * Generate a secure, random temporary password for a newly provisioned
     * account (manual admin creation or bulk Excel import). The caller is
     * responsible for hashing it before storage and for displaying it to
     * the administrator exactly once — it must never be logged or persisted
     * in plain text (spec §20, §22).
     */
    public function generateTemporaryPassword(): string
    {
        return Str::password(14, symbols: false);
    }
}
