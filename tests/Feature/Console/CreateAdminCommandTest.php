<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator_account(): void
    {
        $this->artisan('app:create-admin', [
            '--first-name' => 'Admin',
            '--last-name' => 'User',
            '--email' => 'admin@example.gr',
            '--password' => 'ASecurePass123',
        ])->assertSuccessful();

        $admin = User::where('email', 'admin@example.gr')->firstOrFail();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertFalse($admin->must_change_password);
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.gr']);

        $this->artisan('app:create-admin', [
            '--first-name' => 'Admin',
            '--last-name' => 'Two',
            '--email' => 'admin@example.gr',
            '--password' => 'ASecurePass123',
        ])->assertFailed();

        $this->assertSame(1, User::where('email', 'admin@example.gr')->count());
    }

    public function test_it_rejects_a_weak_password(): void
    {
        $this->artisan('app:create-admin', [
            '--first-name' => 'Admin',
            '--last-name' => 'User',
            '--email' => 'admin@example.gr',
            '--password' => '123',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.gr']);
    }
}
