<?php

namespace Tests\Feature\Booking;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function appointmentOn(User $teacher, Carbon $date, string $start, string $end, array $overrides = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'teacher_id' => $teacher->id,
            'date' => $date->toDateString(),
            'start_time' => $start,
            'end_time' => $end,
        ], $overrides));
    }

    public function test_past_appointments_become_completed_when_a_list_is_opened(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $teacher = User::factory()->teacher()->create();

        $yesterday = $this->appointmentOn($teacher, today()->subDay(), '10:00:00', '10:05:00');
        $endedToday = $this->appointmentOn($teacher, today(), '11:50:00', '11:55:00');
        $inProgress = $this->appointmentOn($teacher, today(), '11:58:00', '12:03:00');
        $laterToday = $this->appointmentOn($teacher, today(), '13:00:00', '13:05:00');
        $tomorrow = $this->appointmentOn($teacher, today()->addDay(), '09:00:00', '09:05:00');
        $cancelledInPast = $this->appointmentOn($teacher, today()->subDay(), '09:00:00', '09:05:00', [
            'status' => AppointmentStatus::Cancelled, 'active_slot_id' => null, 'cancelled_at' => now()->subDays(2),
        ]);

        $this->actingAs($teacher)->get(route('teacher.appointments.index'))->assertOk();

        $this->assertSame(AppointmentStatus::Completed, $yesterday->fresh()->status);
        $this->assertSame(AppointmentStatus::Completed, $endedToday->fresh()->status);
        $this->assertSame(AppointmentStatus::New, $inProgress->fresh()->status);
        $this->assertSame(AppointmentStatus::New, $laterToday->fresh()->status);
        $this->assertSame(AppointmentStatus::New, $tomorrow->fresh()->status);
        $this->assertSame(AppointmentStatus::Cancelled, $cancelledInPast->fresh()->status);
    }

    public function test_guardian_and_admin_pages_also_complete_past_appointments(): void
    {
        $appointment = Appointment::factory()->create([
            'date' => today()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($appointment->guardian)->get(route('guardian.dashboard'))->assertOk();
        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);

        $other = Appointment::factory()->create(['date' => today()->subDays(3)->toDateString()]);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.appointments.index'))->assertOk();
        $this->assertSame(AppointmentStatus::Completed, $other->fresh()->status);
    }

    public function test_an_appointment_that_already_started_cannot_be_cancelled(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $teacher = User::factory()->teacher()->create();
        $inProgress = $this->appointmentOn($teacher, today(), '11:58:00', '12:03:00');

        $this->actingAs($teacher)->patch(route('teacher.appointments.cancel', $inProgress))
            ->assertSessionHasErrors('appointment');
        $this->actingAs($inProgress->guardian)->patch(route('guardian.appointments.cancel', $inProgress))
            ->assertSessionHasErrors('appointment');

        $this->assertSame(AppointmentStatus::New, $inProgress->fresh()->status);
    }

    public function test_the_cancel_button_is_only_shown_for_appointments_that_have_not_started(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $teacher = User::factory()->teacher()->create();
        $inProgress = $this->appointmentOn($teacher, today(), '11:58:00', '12:03:00');
        $past = $this->appointmentOn($teacher, today()->subDay(), '10:00:00', '10:05:00');
        $future = $this->appointmentOn($teacher, today(), '13:00:00', '13:05:00');

        $response = $this->actingAs($teacher)->get(route('teacher.appointments.index'));

        $response->assertSee(route('teacher.appointments.cancel', $future), false);
        $response->assertDontSee(route('teacher.appointments.cancel', $inProgress), false);
        $response->assertDontSee(route('teacher.appointments.cancel', $past), false);
    }

    public function test_lists_show_upcoming_appointments_first_then_past_ones_most_recent_first(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $teacher = User::factory()->teacher()->create();

        // Created deliberately out of order.
        $pastOld = $this->appointmentOn($teacher, today()->subDays(5), '10:00:00', '10:05:00');
        $future2 = $this->appointmentOn($teacher, today()->addDays(3), '09:00:00', '09:05:00');
        $pastRecent = $this->appointmentOn($teacher, today()->subDay(), '10:00:00', '10:05:00');
        $today = $this->appointmentOn($teacher, today(), '13:00:00', '13:05:00');
        $future1 = $this->appointmentOn($teacher, today()->addDay(), '09:00:00', '09:05:00');

        $expected = [$today, $future1, $future2, $pastRecent, $pastOld];

        $this->actingAs($teacher)->get(route('teacher.appointments.index'))
            ->assertSeeInOrder(array_map(fn ($a) => $a->guardian->full_name, $expected));

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.appointments.index'))
            ->assertSeeInOrder(array_map(fn ($a) => route('admin.appointments.cancel', $a), [$today, $future1, $future2]), false)
            ->assertSeeInOrder(array_map(fn ($a) => $a->guardian->full_name, $expected));
    }

    public function test_guardian_dashboard_lists_upcoming_appointments_by_appointment_date_not_booking_time(): void
    {
        $first = Appointment::factory()->create([
            'date' => today()->addDays(10)->toDateString(),
            'booked_at' => now()->subHour(),
        ]);
        $guardian = $first->guardian;
        $second = Appointment::factory()->create([
            'guardian_id' => $guardian->id,
            'child_id' => $first->child_id,
            'date' => today()->addDays(2)->toDateString(),
            'booked_at' => now(),
        ]);

        $this->actingAs($guardian)->get(route('guardian.dashboard'))
            ->assertSeeInOrder([$second->teacher->full_name, $first->teacher->full_name]);
    }
}
