<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianChildManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_child_to_a_guardian(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create();

        $response = $this->actingAs($admin)->post(route('admin.guardians.children.store', $guardian), [
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'class' => 'A1',
        ]);

        $response->assertRedirect(route('admin.guardians.edit', $guardian));
        $this->assertDatabaseHas('children', [
            'guardian_id' => $guardian->id,
            'first_name' => 'Maria',
            'class' => 'A1',
        ]);
    }

    public function test_non_admin_cannot_add_a_child_to_a_guardian(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($teacher)->post(route('admin.guardians.children.store', $guardian), [
            'first_name' => 'Maria', 'last_name' => 'Papadopoulou', 'class' => 'A1',
        ])->assertForbidden();

        $this->assertSame(0, $guardian->children()->count());
    }

    public function test_admin_cannot_add_a_child_via_a_non_guardian_user(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)->post(route('admin.guardians.children.store', $teacher), [
            'first_name' => 'Maria', 'last_name' => 'Papadopoulou', 'class' => 'A1',
        ])->assertNotFound();
    }

    public function test_admin_can_update_a_childs_details(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create(['first_name' => 'Original', 'class' => 'A1']);

        $response = $this->actingAs($admin)->put(route('admin.guardians.children.update', [$guardian, $child]), [
            'first_name' => 'Updated', 'last_name' => $child->last_name, 'class' => 'B1',
        ]);

        $response->assertRedirect(route('admin.guardians.edit', $guardian));
        $this->assertSame('Updated', $child->fresh()->first_name);
        $this->assertSame('B1', $child->fresh()->class);
    }

    public function test_non_admin_cannot_update_a_guardians_child(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create(['first_name' => 'Original']);

        $this->actingAs($teacher)->put(route('admin.guardians.children.update', [$guardian, $child]), [
            'first_name' => 'Hacked', 'last_name' => $child->last_name, 'class' => $child->class,
        ])->assertForbidden();

        $this->assertSame('Original', $child->fresh()->first_name);
    }

    public function test_admin_cannot_update_a_child_belonging_to_a_different_guardian(): void
    {
        $admin = User::factory()->admin()->create();
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        $childOfB = Child::factory()->for($guardianB, 'guardian')->create(['first_name' => 'Original']);

        $this->actingAs($admin)->put(route('admin.guardians.children.update', [$guardianA, $childOfB]), [
            'first_name' => 'Hacked', 'last_name' => $childOfB->last_name, 'class' => $childOfB->class,
        ])->assertNotFound();

        $this->assertSame('Original', $childOfB->fresh()->first_name);
    }

    public function test_admin_can_delete_a_child_with_no_appointment_history(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $response = $this->actingAs($admin)->delete(route('admin.guardians.children.destroy', [$guardian, $child]));

        $response->assertRedirect(route('admin.guardians.edit', $guardian));
        $this->assertDatabaseMissing('children', ['id' => $child->id]);
    }

    public function test_admin_cannot_delete_a_child_with_appointment_history(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();
        $child = $appointment->child;
        $guardian = $child->guardian;

        $response = $this->actingAs($admin)->delete(route('admin.guardians.children.destroy', [$guardian, $child]));

        $response->assertSessionHasErrors('child');
        $this->assertDatabaseHas('children', ['id' => $child->id]);
    }

    public function test_non_admin_cannot_delete_a_guardians_child(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $this->actingAs($teacher)->delete(route('admin.guardians.children.destroy', [$guardian, $child]))->assertForbidden();

        $this->assertDatabaseHas('children', ['id' => $child->id]);
    }

    public function test_admin_cannot_delete_a_child_belonging_to_a_different_guardian(): void
    {
        $admin = User::factory()->admin()->create();
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        $childOfB = Child::factory()->for($guardianB, 'guardian')->create();

        $this->actingAs($admin)->delete(route('admin.guardians.children.destroy', [$guardianA, $childOfB]))->assertNotFound();

        $this->assertDatabaseHas('children', ['id' => $childOfB->id]);
    }
}
