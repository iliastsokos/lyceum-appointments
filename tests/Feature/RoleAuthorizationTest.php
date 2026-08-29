<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_guardian_to_guardian_dashboard(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->get('/dashboard')
            ->assertRedirect(route('guardian.dashboard'));
    }

    public function test_dashboard_redirects_teacher_to_teacher_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/dashboard')
            ->assertRedirect(route('teacher.dashboard'));
    }

    public function test_dashboard_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_guardian_cannot_access_teacher_dashboard(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->get(route('teacher.dashboard'))->assertForbidden();
    }

    public function test_guardian_cannot_access_admin_area(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($guardian)->get(route('admin.teachers.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('admin.guardians.index'))->assertForbidden();
    }

    public function test_teacher_cannot_access_admin_area(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_teacher_cannot_access_guardian_area(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('guardian.dashboard'))->assertForbidden();
    }

    public function test_admin_cannot_access_teacher_or_guardian_dashboards(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('teacher.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('guardian.dashboard'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $this->get(route('guardian.dashboard'))->assertRedirect(route('login'));
        $this->get(route('teacher.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_inactive_user_is_forbidden_from_protected_routes(): void
    {
        $guardian = User::factory()->guardian()->inactive()->create();

        $this->actingAs($guardian)->get(route('guardian.dashboard'))->assertForbidden();
    }
}
