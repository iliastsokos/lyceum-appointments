<?php

namespace Tests\Feature\Booking;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_cancel_their_own_appointment(): void
    {
        $appointment = Appointment::factory()->create();
        $guardian = $appointment->guardian;

        $response = $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment));

        $response->assertRedirect(route('guardian.appointments.index'));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertNull($appointment->active_slot_id);
    }

    public function test_cancelling_an_appointment_frees_the_slot(): void
    {
        $appointment = Appointment::factory()->create();
        $guardian = $appointment->guardian;

        $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment));

        $this->assertSame(SlotStatus::Available, $appointment->slot->fresh()->status);
    }

    public function test_cancelled_slot_can_be_rebooked_by_another_guardian(): void
    {
        $appointment = Appointment::factory()->create();
        $guardian = $appointment->guardian;
        $slot = $appointment->slot;

        $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment));

        $newGuardian = User::factory()->guardian()->create();
        $newChild = Child::factory()->for($newGuardian, 'guardian')->create();

        $response = $this->actingAs($newGuardian)->post(route('guardian.book.store', [
            'teacher' => $slot->teacher, 'slot' => $slot,
        ]), ['child_id' => $newChild->id]);

        $response->assertRedirect(route('guardian.dashboard'));
        $this->assertSame(2, Appointment::where('slot_id', $slot->id)->count());
        $this->assertSame(1, Appointment::where('slot_id', $slot->id)->where('status', '!=', 'cancelled')->count());
    }

    public function test_cancellation_creates_a_notification_for_the_teacher(): void
    {
        $appointment = Appointment::factory()->create();
        $guardian = $appointment->guardian;

        $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $appointment->teacher_id,
            'type' => 'appointment_cancelled',
        ]);
    }

    public function test_guardian_cannot_cancel_another_guardians_appointment(): void
    {
        $appointment = Appointment::factory()->create();
        $otherGuardian = User::factory()->guardian()->create();

        $this->actingAs($otherGuardian)->patch(route('guardian.appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertSame(AppointmentStatus::New, $appointment->fresh()->status);
    }

    public function test_cancelling_an_already_cancelled_appointment_is_rejected(): void
    {
        $appointment = Appointment::factory()->cancelled()->create();
        $guardian = $appointment->guardian;

        $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment))
            ->assertSessionHasErrors('appointment');
    }

    public function test_guardian_can_provide_a_cancellation_reason(): void
    {
        $appointment = Appointment::factory()->create();
        $guardian = $appointment->guardian;

        $this->actingAs($guardian)->patch(route('guardian.appointments.cancel', $appointment), [
            'reason' => 'Schedule conflict',
        ]);

        $this->assertSame('Schedule conflict', $appointment->fresh()->cancellation_reason);
    }
}
