<?php

namespace Tests\Feature\Booking;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\NotificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeacherAndAdminCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_cancel_an_appointment_booked_with_them(): void
    {
        $appointment = Appointment::factory()->create();
        $teacher = $appointment->teacher;

        $response = $this->actingAs($teacher)->patch(route('teacher.appointments.cancel', $appointment));

        $response->assertRedirect(route('teacher.appointments.index'));
        $response->assertSessionHas('status', 'appointment-cancelled');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertNull($appointment->active_slot_id);
        $this->assertSame($teacher->id, $appointment->cancelled_by);
    }

    public function test_teacher_cancellation_disables_the_slot_instead_of_reopening_it(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($appointment->teacher)->patch(route('teacher.appointments.cancel', $appointment));

        $this->assertSame(SlotStatus::Disabled, $appointment->slot->fresh()->status);
    }

    public function test_teacher_cancellation_notifies_the_guardian_in_app_and_by_email(): void
    {
        Notification::fake();

        $appointment = Appointment::factory()->create();
        $teacher = $appointment->teacher;
        $guardian = $appointment->guardian;

        $this->actingAs($teacher)->patch(route('teacher.appointments.cancel', $appointment), [
            'reason' => 'Απουσία λόγω σεμιναρίου',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $guardian->id,
            'type' => 'appointment_cancelled',
            'message' => sprintf(
                'Ο/Η εκπαιδευτικός %s ακύρωσε το ραντεβού σας για τον/την %s στις %s και ώρα %s. Αιτιολογία: Απουσία λόγω σεμιναρίου',
                $teacher->full_name,
                $appointment->child->full_name,
                $appointment->date->translatedFormat('d/m/Y'),
                substr($appointment->start_time, 0, 5),
            ),
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $teacher->id]);

        Notification::assertSentTo($guardian, NotificationMail::class, function (NotificationMail $notification) {
            return $notification->toMail($notification)->subject === 'Ακύρωση ραντεβού';
        });
        Notification::assertNotSentTo($teacher, NotificationMail::class);
        $this->assertSame('Απουσία λόγω σεμιναρίου', $appointment->fresh()->cancellation_reason);
    }

    public function test_teacher_cannot_cancel_another_teachers_appointment(): void
    {
        $appointment = Appointment::factory()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($otherTeacher)->patch(route('teacher.appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertSame(AppointmentStatus::New, $appointment->fresh()->status);
    }

    public function test_teacher_cannot_cancel_an_already_cancelled_appointment(): void
    {
        $appointment = Appointment::factory()->cancelled()->create();

        $this->actingAs($appointment->teacher)->patch(route('teacher.appointments.cancel', $appointment))
            ->assertSessionHasErrors('appointment');
    }

    public function test_guardian_cannot_use_the_teacher_or_admin_cancel_routes(): void
    {
        $appointment = Appointment::factory()->create();
        $guardian = $appointment->guardian;

        $this->actingAs($guardian)->patch(route('teacher.appointments.cancel', $appointment))->assertForbidden();
        $this->actingAs($guardian)->patch(route('admin.appointments.cancel', $appointment))->assertForbidden();

        $this->assertSame(AppointmentStatus::New, $appointment->fresh()->status);
    }

    public function test_teacher_cannot_use_the_admin_cancel_route(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($appointment->teacher)->patch(route('admin.appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertSame(AppointmentStatus::New, $appointment->fresh()->status);
    }

    public function test_admin_can_cancel_on_the_teachers_behalf_and_both_sides_are_notified(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.appointments.index'))
            ->patch(route('admin.appointments.cancel', $appointment), ['reason' => 'Ασθένεια εκπαιδευτικού'])
            ->assertRedirect(route('admin.appointments.index'));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertSame($admin->id, $appointment->cancelled_by);
        $this->assertSame(SlotStatus::Disabled, $appointment->slot->fresh()->status);

        foreach ([$appointment->guardian, $appointment->teacher] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id,
                'type' => 'appointment_cancelled',
            ]);
            Notification::assertSentTo($recipient, NotificationMail::class);
        }

        $this->assertStringStartsWith(
            'Η Διεύθυνση του σχολείου ακύρωσε',
            $appointment->guardian->notifications()->firstOrFail()->message,
        );
        $this->assertStringEndsWith(
            'Αιτιολογία: Ασθένεια εκπαιδευτικού',
            $appointment->teacher->notifications()->firstOrFail()->message,
        );
    }

    public function test_guardian_cancellation_is_recorded_and_still_frees_the_slot(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($appointment->guardian)->patch(route('guardian.appointments.cancel', $appointment));

        $appointment->refresh();
        $this->assertSame($appointment->guardian_id, $appointment->cancelled_by);
        $this->assertSame(SlotStatus::Available, $appointment->slot->fresh()->status);
        $this->assertDatabaseMissing('notifications', ['user_id' => $appointment->guardian_id]);
    }

    public function test_cancellation_reason_is_limited_in_length(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($appointment->teacher)
            ->patch(route('teacher.appointments.cancel', $appointment), ['reason' => str_repeat('α', 501)])
            ->assertSessionHasErrors('reason');

        $this->assertSame(AppointmentStatus::New, $appointment->fresh()->status);
    }

    public function test_teacher_appointment_list_shows_the_cancel_button_only_for_active_appointments(): void
    {
        $active = Appointment::factory()->create();
        $teacher = $active->teacher;
        $cancelled = Appointment::factory()->cancelled()->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('teacher.appointments.index'));

        $response->assertOk();
        $response->assertSee(route('teacher.appointments.cancel', $active), false);
        $response->assertDontSee(route('teacher.appointments.cancel', $cancelled), false);
    }

    public function test_lists_show_who_cancelled_the_appointment(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($appointment->teacher)->patch(route('teacher.appointments.cancel', $appointment), [
            'reason' => 'Απουσία',
        ]);

        $this->actingAs($appointment->guardian)->get(route('guardian.appointments.index'))
            ->assertSee('Ακυρώθηκε από τον/την εκπαιδευτικό')
            ->assertSee('Απουσία');
    }

    public function test_admin_appointment_list_can_be_filtered_by_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $mine = Appointment::factory()->create();
        $other = Appointment::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.appointments.index', ['teacher_id' => $mine->teacher_id]));

        $response->assertOk();
        $response->assertSee(route('admin.appointments.cancel', $mine), false);
        $response->assertDontSee(route('admin.appointments.cancel', $other), false);
    }

    public function test_non_admins_cannot_see_the_admin_appointment_list(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('admin.appointments.index'))->assertForbidden();
    }
}
