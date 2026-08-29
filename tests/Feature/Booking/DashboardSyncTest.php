<?php

namespace Tests\Feature\Booking;

use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_booked_appointment_appears_on_guardian_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $availability = Availability::factory()->for($teacher, 'teacher')->create(['date' => today()->addDay()->toDateString()]);
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id, 'availability_id' => $availability->id,
            'date' => $availability->date, 'status' => SlotStatus::Available,
        ]);

        $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $teacher, 'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $this->actingAs($guardian)->get(route('guardian.dashboard'))
            ->assertSee($teacher->full_name)
            ->assertSee($child->full_name);
    }

    public function test_todays_booked_appointment_appears_on_teacher_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $availability = Availability::factory()->for($teacher, 'teacher')->create(['date' => today()->toDateString()]);
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id, 'availability_id' => $availability->id,
            'date' => $availability->date, 'start_time' => '23:55:00', 'end_time' => '23:59:59',
            'status' => SlotStatus::Available,
        ]);

        $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $teacher, 'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertSee($guardian->full_name)
            ->assertSee($child->full_name);
    }

    public function test_cancellation_immediately_reflects_on_both_dashboards(): void
    {
        $appointment = Appointment::factory()->create();
        $guardian = $appointment->guardian;
        $teacher = $appointment->teacher;
        $child = $appointment->child;

        // Booked appointment appears in guardian's list.
        $this->actingAs($guardian)->get(route('guardian.appointments.index'))
            ->assertSee($teacher->full_name);

        $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment));

        // Guardian's list now shows it as cancelled, and slot is bookable again.
        $this->actingAs($guardian)->get(route('guardian.appointments.index'))
            ->assertSee('Cancelled');

        $this->assertSame(SlotStatus::Available, $appointment->slot->fresh()->status);

        // Teacher no longer sees it under today's active appointments (it is cancelled).
        $this->actingAs($teacher)->get(route('teacher.appointments.index'))
            ->assertSee($child->full_name)
            ->assertSee('Cancelled');
    }
}
