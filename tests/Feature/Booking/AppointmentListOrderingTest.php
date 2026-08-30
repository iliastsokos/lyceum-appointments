<?php

namespace Tests\Feature\Booking;

use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentListOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_appointments_are_ordered_by_appointment_date_not_booking_time(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $laterSlot = $this->makeSlotOnDate(today()->addDays(10));
        $earlierSlot = $this->makeSlotOnDate(today()->addDays(1));

        // Book the LATER appointment first (earlier booked_at) and the
        // EARLIER appointment second (later booked_at) — the old
        // `orderByDesc('booked_at')` would have listed these in the
        // opposite order from what's asserted below.
        $laterAppointment = Appointment::factory()->create([
            'slot_id' => $laterSlot->id,
            'active_slot_id' => $laterSlot->id,
            'teacher_id' => $laterSlot->teacher_id,
            'guardian_id' => $guardian->id,
            'child_id' => $child->id,
            'date' => $laterSlot->date,
            'start_time' => $laterSlot->start_time,
            'end_time' => $laterSlot->end_time,
            'booked_at' => now()->subMinutes(10),
        ]);
        $earlierAppointment = Appointment::factory()->create([
            'slot_id' => $earlierSlot->id,
            'active_slot_id' => $earlierSlot->id,
            'teacher_id' => $earlierSlot->teacher_id,
            'guardian_id' => $guardian->id,
            'child_id' => $child->id,
            'date' => $earlierSlot->date,
            'start_time' => $earlierSlot->start_time,
            'end_time' => $earlierSlot->end_time,
            'booked_at' => now(),
        ]);

        $response = $this->actingAs($guardian)->get(route('guardian.appointments.index'));

        $response->assertSeeInOrder([
            $earlierAppointment->teacher->full_name,
            $laterAppointment->teacher->full_name,
        ]);
    }

    public function test_teacher_appointments_are_ordered_by_appointment_date_not_booking_time(): void
    {
        $teacher = User::factory()->teacher()->create();

        $laterSlot = $this->makeSlotOnDate(today()->addDays(10), $teacher);
        $earlierSlot = $this->makeSlotOnDate(today()->addDays(1), $teacher);

        $laterGuardian = User::factory()->guardian()->create();
        $laterChild = Child::factory()->for($laterGuardian, 'guardian')->create();
        $earlierGuardian = User::factory()->guardian()->create();
        $earlierChild = Child::factory()->for($earlierGuardian, 'guardian')->create();

        Appointment::factory()->create([
            'slot_id' => $laterSlot->id,
            'active_slot_id' => $laterSlot->id,
            'teacher_id' => $teacher->id,
            'guardian_id' => $laterGuardian->id,
            'child_id' => $laterChild->id,
            'date' => $laterSlot->date,
            'start_time' => $laterSlot->start_time,
            'end_time' => $laterSlot->end_time,
            'booked_at' => now()->subMinutes(10),
        ]);
        Appointment::factory()->create([
            'slot_id' => $earlierSlot->id,
            'active_slot_id' => $earlierSlot->id,
            'teacher_id' => $teacher->id,
            'guardian_id' => $earlierGuardian->id,
            'child_id' => $earlierChild->id,
            'date' => $earlierSlot->date,
            'start_time' => $earlierSlot->start_time,
            'end_time' => $earlierSlot->end_time,
            'booked_at' => now(),
        ]);

        $response = $this->actingAs($teacher)->get(route('teacher.appointments.index'));

        $response->assertSeeInOrder([
            $earlierChild->full_name,
            $laterChild->full_name,
        ]);
    }

    private function makeSlotOnDate(Carbon $date, ?User $teacher = null): AppointmentSlot
    {
        $teacher ??= User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => $date->toDateString(),
        ]);

        return AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date->toDateString(),
            'status' => SlotStatus::Booked,
        ]);
    }
}
