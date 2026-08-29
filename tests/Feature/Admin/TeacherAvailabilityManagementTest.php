<?php

namespace Tests\Feature\Admin;

use App\Enums\SlotStatus;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAvailabilityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_availability_for_a_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $date = today()->addDay()->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.teachers.availability.store', $teacher), [
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '09:15',
        ]);

        $response->assertRedirect(route('admin.teachers.availability.index', $teacher));

        $availability = Availability::where('teacher_id', $teacher->id)->firstOrFail();
        $this->assertSame(3, $availability->slots()->count());
    }

    public function test_admin_availability_creation_is_validated_the_same_as_the_teachers_own(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($admin)->post(route('admin.teachers.availability.store', $teacher), [
            'date' => today()->subDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertSame(0, Availability::where('teacher_id', $teacher->id)->count());
    }

    public function test_non_admin_cannot_create_availability_through_the_admin_route(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($otherTeacher)->post(route('admin.teachers.availability.store', $teacher), [
            'date' => today()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '09:15',
        ])->assertForbidden();

        $this->assertSame(0, Availability::where('teacher_id', $teacher->id)->count());
    }

    public function test_admin_can_view_a_teachers_availability_list(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        Availability::factory()->for($teacher, 'teacher')->create(['start_time' => '10:00:00', 'end_time' => '10:05:00']);

        $response = $this->actingAs($admin)->get(route('admin.teachers.availability.index', $teacher));

        $response->assertOk();
        $response->assertSee('10:00–10:05');
    }

    public function test_non_admin_cannot_view_a_teachers_availability_through_the_admin_route(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->get(route('admin.teachers.availability.index', $teacher))->assertForbidden();
    }

    public function test_admin_can_toggle_a_teachers_slot(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Available,
        ]);

        $this->actingAs($admin)->patch(route('admin.teachers.availability.slots.toggle', [$teacher, $slot]))
            ->assertRedirect(route('admin.teachers.availability.show', [$teacher, $availability]));

        $this->assertSame(SlotStatus::Disabled, $slot->fresh()->status);
    }

    public function test_admin_cannot_toggle_a_booked_slot(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Booked,
        ]);

        $this->actingAs($admin)->patch(route('admin.teachers.availability.slots.toggle', [$teacher, $slot]))
            ->assertSessionHasErrors('slot');

        $this->assertSame(SlotStatus::Booked, $slot->fresh()->status);
    }

    public function test_admin_can_remove_availability_with_no_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Available,
        ]);

        $this->actingAs($admin)->delete(route('admin.teachers.availability.destroy', [$teacher, $availability]))
            ->assertRedirect(route('admin.teachers.availability.index', $teacher));

        $this->assertDatabaseMissing('availability', ['id' => $availability->id]);
    }

    public function test_admin_cannot_remove_availability_with_booked_slots(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Booked,
        ]);

        $this->actingAs($admin)->delete(route('admin.teachers.availability.destroy', [$teacher, $availability]))
            ->assertSessionHasErrors('availability');

        $this->assertDatabaseHas('availability', ['id' => $availability->id]);
    }

    public function test_admin_cannot_manage_availability_through_a_mismatched_teacher_id(): void
    {
        $admin = User::factory()->admin()->create();
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacherB, 'teacher')->create();

        $this->actingAs($admin)->get(route('admin.teachers.availability.show', [$teacherA, $availability]))
            ->assertNotFound();
    }
}
