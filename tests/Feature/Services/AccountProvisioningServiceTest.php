<?php

namespace Tests\Feature\Services;

use App\Services\AccountProvisioningService;
use Tests\TestCase;

class AccountProvisioningServiceTest extends TestCase
{
    public function test_generated_temporary_passwords_are_four_characters(): void
    {
        $service = new AccountProvisioningService;

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame(4, strlen($service->generateTemporaryPassword()));
        }
    }
}
