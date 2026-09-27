<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_run_pending_migrations(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.system.migrate'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('status', 'migrations-run');
    }

    public function test_running_maintenance_also_clears_the_framework_caches(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.system.migrate'));

        $output = session('migrateOutput');
        $this->assertStringContainsString('Configuration cache cleared', $output);
        $this->assertStringContainsString('Route cache cleared', $output);
        $this->assertStringContainsString('Compiled views cleared', $output);
    }

    public function test_non_admin_cannot_run_migrations(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->post(route('admin.system.migrate'))->assertForbidden();
    }
}
