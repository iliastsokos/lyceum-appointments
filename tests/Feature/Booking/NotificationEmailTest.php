<?php

namespace Tests\Feature\Booking;

use App\Enums\SlotStatus;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Child;
use App\Models\User;
use App\Notifications\NotificationMail;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class NotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_a_slot_emails_the_teacher_in_parallel_with_the_in_app_notification(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot($teacher);

        $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $teacher, 'slot' => $slot,
        ]), ['child_id' => $child->id]);

        Notification::assertSentTo($teacher, NotificationMail::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacher->id,
            'type' => 'appointment_booked',
        ]);
    }

    public function test_cancelling_an_appointment_emails_the_teacher_in_parallel_with_the_in_app_notification(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot($teacher);

        $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $teacher, 'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $appointment = $guardian->appointmentsAsGuardian()->firstOrFail();

        $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment));

        Notification::assertSentTo($teacher, NotificationMail::class, function (NotificationMail $notification) {
            $mail = $notification->toMail($notification);

            return $mail->subject === 'Ακύρωση ραντεβού';
        });
    }

    public function test_a_failed_notification_email_does_not_prevent_the_notification_from_being_recorded(): void
    {
        $teacher = Mockery::mock(User::factory()->create())->makePartial();
        $teacher->shouldReceive('notify')->once()->andThrow(new RuntimeException('SMTP unreachable'));

        $notification = (new NotificationService)->send($teacher, 'appointment_booked', 'Νέο ραντεβού', 'Δοκιμαστικό μήνυμα.');

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $teacher->id,
            'type' => 'appointment_booked',
        ]);
    }

    private function makeSlot(User $teacher): AppointmentSlot
    {
        $availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->addDay()->toDateString(),
        ]);

        return AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '11:05:00',
            'status' => SlotStatus::Available,
        ]);
    }
}
