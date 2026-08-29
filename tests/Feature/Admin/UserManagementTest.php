<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_teacher_with_generated_temporary_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'email' => 'maria@example.gr',
            'subject' => 'Mathematics',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));
        $response->assertSessionHas('temporaryPassword');

        $teacher = User::where('email', 'maria@example.gr')->firstOrFail();
        $this->assertSame(UserRole::Teacher, $teacher->role);
        $this->assertTrue($teacher->must_change_password);
        $this->assertTrue(Hash::check(session('temporaryPassword'), $teacher->password));
    }

    public function test_admin_can_create_a_guardian_with_generated_temporary_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.guardians.store'), [
            'first_name' => 'Giorgos',
            'last_name' => 'Papadopoulos',
            'email' => 'gpap@example.gr',
            'phone' => '6900000000',
        ]);

        $response->assertRedirect(route('admin.guardians.index'));

        $guardian = User::where('email', 'gpap@example.gr')->firstOrFail();
        $this->assertSame(UserRole::Guardian, $guardian->role);
        $this->assertTrue($guardian->must_change_password);
    }

    public function test_admin_cannot_create_teacher_with_invalid_role_via_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->create(['email' => 'taken@example.gr']);

        $response = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'first_name' => 'Nikos',
            'last_name' => 'Ioannidis',
            'email' => 'taken@example.gr',
            'subject' => 'Physics',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_deactivate_and_reactivate_a_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)->patch(route('admin.teachers.toggle-status', $teacher))
            ->assertRedirect(route('admin.teachers.index'));
        $this->assertSame(UserStatus::Inactive, $teacher->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.teachers.toggle-status', $teacher));
        $this->assertSame(UserStatus::Active, $teacher->fresh()->status);
    }

    public function test_deactivated_teacher_cannot_log_into_protected_area(): void
    {
        $teacher = User::factory()->teacher()->inactive()->create();

        $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertForbidden();
    }

    public function test_non_admin_cannot_create_teachers(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->post(route('admin.teachers.store'), [
            'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.gr', 'subject' => 'Physics',
        ])->assertForbidden();
    }

    public function test_admin_cannot_edit_teacher_through_guardian_route_id_mismatch(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create();

        // Attempting to edit a guardian's user id via the teachers admin route must 404,
        // not silently save teacher-only fields onto a guardian record.
        $this->actingAs($admin)->get(route('admin.teachers.edit', $guardian))->assertNotFound();
    }

    public function test_admin_cannot_update_teacher_through_guardian_route_id_mismatch(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create(['first_name' => 'Original']);

        $this->actingAs($admin)->put(route('admin.teachers.update', $guardian), [
            'first_name' => 'Hacked', 'last_name' => 'Name', 'email' => $guardian->email, 'subject' => 'Physics',
        ])->assertNotFound();

        $this->assertSame('Original', $guardian->fresh()->first_name);
    }

    public function test_non_admin_cannot_update_a_teacher(): void
    {
        $otherTeacher = User::factory()->teacher()->create();
        $teacher = User::factory()->teacher()->create(['first_name' => 'Original']);

        $this->actingAs($otherTeacher)->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Hacked', 'last_name' => 'Name', 'email' => $teacher->email, 'subject' => 'Physics',
        ])->assertForbidden();

        $this->assertSame('Original', $teacher->fresh()->first_name);
    }

    public function test_non_admin_cannot_update_a_guardian(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create(['first_name' => 'Original']);

        $this->actingAs($teacher)->put(route('admin.guardians.update', $guardian), [
            'first_name' => 'Hacked', 'last_name' => 'Name', 'email' => $guardian->email,
        ])->assertForbidden();

        $this->assertSame('Original', $guardian->fresh()->first_name);
    }

    public function test_admin_can_update_a_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create(['first_name' => 'Original']);

        $this->actingAs($admin)->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Updated', 'last_name' => $teacher->last_name, 'email' => $teacher->email, 'subject' => $teacher->subject,
        ])->assertRedirect(route('admin.teachers.index'));

        $this->assertSame('Updated', $teacher->fresh()->first_name);
    }

    public function test_admin_can_view_teacher_create_and_edit_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)->get(route('admin.teachers.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.teachers.edit', $teacher))->assertOk();
    }

    public function test_admin_can_view_guardian_create_and_edit_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($admin)->get(route('admin.guardians.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.guardians.edit', $guardian))->assertOk();
    }

    public function test_admin_search_filters_teacher_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->create(['first_name' => 'Findme', 'last_name' => 'Teacher']);
        User::factory()->teacher()->create(['first_name' => 'Other', 'last_name' => 'Teacher']);

        $response = $this->actingAs($admin)->get(route('admin.teachers.index', ['search' => 'Findme']));

        $response->assertSee('Findme');
        $response->assertDontSee('Other Teacher');
    }

    public function test_admin_can_delete_a_teacher_with_no_history(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)->delete(route('admin.teachers.destroy', $teacher))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
    }

    public function test_admin_cannot_delete_a_teacher_with_availability_history(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        \App\Models\Availability::factory()->for($teacher, 'teacher')->create();

        $response = $this->actingAs($admin)->delete(route('admin.teachers.destroy', $teacher));

        $response->assertRedirect(route('admin.teachers.index'));
        $response->assertSessionHasErrors('teacher');
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    }

    public function test_non_admin_cannot_delete_a_teacher(): void
    {
        $otherTeacher = User::factory()->teacher()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($otherTeacher)->delete(route('admin.teachers.destroy', $teacher))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    }

    public function test_admin_can_delete_a_guardian_with_no_children(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($admin)->delete(route('admin.guardians.destroy', $guardian))
            ->assertRedirect(route('admin.guardians.index'));

        $this->assertDatabaseMissing('users', ['id' => $guardian->id]);
    }

    public function test_admin_cannot_delete_a_guardian_with_children(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create();
        \App\Models\Child::factory()->for($guardian, 'guardian')->create();

        $response = $this->actingAs($admin)->delete(route('admin.guardians.destroy', $guardian));

        $response->assertRedirect(route('admin.guardians.index'));
        $response->assertSessionHasErrors('guardian');
        $this->assertDatabaseHas('users', ['id' => $guardian->id]);
    }

    public function test_non_admin_cannot_delete_a_guardian(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($teacher)->delete(route('admin.guardians.destroy', $guardian))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $guardian->id]);
    }
}
