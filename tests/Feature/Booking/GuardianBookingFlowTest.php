<?php

namespace Tests\Feature\Booking;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeSlot(?User $teacher = null): AppointmentSlot
    {
        $teacher ??= User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->addDay()->toDateString(),
        ]);

        return AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '17:05:00',
            'status' => SlotStatus::Available,
        ]);
    }

    public function test_guardian_sees_only_active_teachers_when_starting_to_book(): void
    {
        $guardian = User::factory()->guardian()->create();
        $activeTeacher = User::factory()->teacher()->create(['first_name' => 'Active']);
        $inactiveTeacher = User::factory()->teacher()->inactive()->create(['first_name' => 'Inactive']);

        $response = $this->actingAs($guardian)->get(route('guardian.book.teachers'));

        $response->assertSee('Active');
        $response->assertDontSee('Inactive');
    }

    public function test_guardian_can_see_available_slots_for_a_date(): void
    {
        $guardian = User::factory()->guardian()->create();
        $slot = $this->makeSlot();

        $response = $this->actingAs($guardian)->get(route('guardian.book.date', [
            'teacher' => $slot->teacher,
            'date' => $slot->date->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('17:00');
    }

    public function test_visiting_a_teacher_fresh_auto_selects_the_first_available_date(): void
    {
        $guardian = User::factory()->guardian()->create();
        $slot = $this->makeSlot();

        // No `date` or `month` query param — a guardian just clicked
        // through from the teacher list.
        $response = $this->actingAs($guardian)->get(route('guardian.book.date', [
            'teacher' => $slot->teacher,
        ]));

        $response->assertOk();
        $response->assertSee('17:00');
    }

    public function test_visiting_a_teacher_fresh_selects_the_nearest_date_even_far_out(): void
    {
        $guardian = User::factory()->guardian()->create();
        $teacher = User::factory()->teacher()->create();
        $farDate = today()->addDays(40);
        $availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => $farDate->toDateString(),
        ]);
        AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '09:05:00',
            'status' => SlotStatus::Available,
        ]);

        $response = $this->actingAs($guardian)->get(route('guardian.book.date', [
            'teacher' => $teacher,
        ]));

        $response->assertOk();
        $response->assertSee('09:00');
    }

    public function test_visiting_a_teacher_with_no_availability_shows_a_helpful_message(): void
    {
        $guardian = User::factory()->guardian()->create();
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($guardian)->get(route('guardian.book.date', [
            'teacher' => $teacher,
        ]));

        $response->assertOk();
        $response->assertSee('δεν έχει ανοίξει διαθέσιμες ημερομηνίες', false);
    }

    public function test_guardian_can_switch_to_a_different_available_date(): void
    {
        $guardian = User::factory()->guardian()->create();
        $teacher = User::factory()->teacher()->create();

        $firstAvailability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->addDay()->toDateString(),
        ]);
        AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $firstAvailability->id,
            'date' => $firstAvailability->date->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:05:00',
            'status' => SlotStatus::Available,
        ]);

        $secondAvailability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->addDays(3)->toDateString(),
        ]);
        $secondSlot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $secondAvailability->id,
            'date' => $secondAvailability->date->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '14:05:00',
            'status' => SlotStatus::Available,
        ]);

        $response = $this->actingAs($guardian)->get(route('guardian.book.date', [
            'teacher' => $teacher,
            'date' => $secondSlot->date->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('14:00');
        $response->assertDontSee('10:00');
    }

    public function test_requesting_a_date_not_in_the_available_list_falls_back_to_the_nearest(): void
    {
        $guardian = User::factory()->guardian()->create();
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->addDay()->toDateString(),
        ]);
        AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:05:00',
            'status' => SlotStatus::Available,
        ]);

        // A date that isn't actually open (stale link, tampered URL) — the
        // page has no "empty date" state any more, so this should just
        // fall back to the one date that genuinely has slots.
        $response = $this->actingAs($guardian)->get(route('guardian.book.date', [
            'teacher' => $teacher,
            'date' => today()->addDays(2)->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('10:00');
    }

    public function test_date_picking_page_links_back_to_the_teacher_list(): void
    {
        $guardian = User::factory()->guardian()->create();
        $slot = $this->makeSlot();

        $response = $this->actingAs($guardian)->get(route('guardian.book.date', [
            'teacher' => $slot->teacher,
            'date' => $slot->date->toDateString(),
        ]));

        $response->assertSee(route('guardian.book.teachers'), false);
        $response->assertSee('Επιστροφή στη λίστα εκπαιδευτικών');
        $response->assertDontSee('Ακύρωση');
    }

    public function test_confirm_page_links_back_to_the_teacher_list(): void
    {
        $guardian = User::factory()->guardian()->create();
        $slot = $this->makeSlot();

        $response = $this->actingAs($guardian)->get(route('guardian.book.confirm', [
            'teacher' => $slot->teacher,
            'slot' => $slot,
        ]));

        $response->assertSee(route('guardian.book.teachers'), false);
        $response->assertSee('Επιστροφή στη λίστα εκπαιδευτικών');
        $response->assertDontSee('Ακύρωση');
    }

    public function test_guardian_can_book_an_available_slot_for_their_child(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot();

        $response = $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $slot->teacher,
            'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $response->assertRedirect(route('guardian.dashboard'));

        $this->assertDatabaseHas('appointments', [
            'slot_id' => $slot->id,
            'guardian_id' => $guardian->id,
            'child_id' => $child->id,
            'status' => AppointmentStatus::New->value,
        ]);
        $this->assertSame(SlotStatus::Booked, $slot->fresh()->status);
    }

    public function test_booking_creates_a_notification_for_the_teacher(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot();

        $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $slot->teacher,
            'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $slot->teacher_id,
            'type' => 'appointment_booked',
        ]);
    }

    public function test_guardian_cannot_reach_confirm_page_for_a_deactivated_teacher(): void
    {
        $teacher = User::factory()->teacher()->inactive()->create();
        $guardian = User::factory()->guardian()->create();
        $slot = $this->makeSlot($teacher);

        $this->actingAs($guardian)->get(route('guardian.book.confirm', [
            'teacher' => $teacher, 'slot' => $slot,
        ]))->assertNotFound();
    }

    public function test_guardian_cannot_book_a_slot_for_a_deactivated_teacher(): void
    {
        $teacher = User::factory()->teacher()->inactive()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot($teacher);

        $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $teacher, 'slot' => $slot,
        ]), ['child_id' => $child->id])->assertNotFound();

        $this->assertDatabaseMissing('appointments', ['slot_id' => $slot->id]);
    }

    public function test_guardian_cannot_book_a_slot_for_another_guardians_child(): void
    {
        $guardian = User::factory()->guardian()->create();
        $otherGuardian = User::factory()->guardian()->create();
        $otherChild = Child::factory()->for($otherGuardian, 'guardian')->create();
        $slot = $this->makeSlot();

        $response = $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $slot->teacher,
            'slot' => $slot,
        ]), ['child_id' => $otherChild->id]);

        $response->assertSessionHasErrors('child_id');
        $this->assertDatabaseMissing('appointments', ['slot_id' => $slot->id]);
    }

    public function test_guardian_cannot_book_an_already_booked_slot(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot();
        $slot->update(['status' => SlotStatus::Booked]);

        $response = $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $slot->teacher,
            'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $response->assertSessionHasErrors('slot');
        $this->assertSame(0, Appointment::where('slot_id', $slot->id)->count());
    }

    public function test_guardian_cannot_book_a_disabled_slot(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot();
        $slot->update(['status' => SlotStatus::Disabled]);

        $this->actingAs($guardian)->get(route('guardian.book.confirm', [
            'teacher' => $slot->teacher,
            'slot' => $slot,
        ]))->assertNotFound();
    }

    public function test_guardian_cannot_book_a_slot_in_the_past(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $availability = Availability::factory()->for($teacher, 'teacher')->create([
            'date' => today()->toDateString(),
        ]);
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '00:05:00',
            'status' => SlotStatus::Available,
        ]);

        $response = $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $teacher,
            'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $response->assertSessionHasErrors('slot');
    }

    public function test_duplicate_booking_attempt_on_same_slot_fails_gracefully(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();
        $slot = $this->makeSlot();

        $this->actingAs($guardian)->post(route('guardian.book.store', [
            'teacher' => $slot->teacher, 'slot' => $slot,
        ]), ['child_id' => $child->id]);

        $secondGuardian = User::factory()->guardian()->create();
        $secondChild = Child::factory()->for($secondGuardian, 'guardian')->create();

        $response = $this->actingAs($secondGuardian)->post(route('guardian.book.store', [
            'teacher' => $slot->teacher, 'slot' => $slot,
        ]), ['child_id' => $secondChild->id]);

        $response->assertSessionHasErrors('slot');
        $this->assertSame(1, Appointment::where('slot_id', $slot->id)->count());
    }
}
