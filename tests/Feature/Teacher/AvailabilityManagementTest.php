<?php

namespace Tests\Feature\Teacher;

use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Availability;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_availability_and_5_minute_slots_are_generated(): void
    {
        $teacher = User::factory()->teacher()->create();
        $date = today()->addDay()->toDateString();

        $response = $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => $date,
            'start_time' => '17:00',
            'end_time' => '19:00',
        ]);

        $response->assertRedirect(route('teacher.availability.index'));

        $availability = Availability::where('teacher_id', $teacher->id)->firstOrFail();
        $this->assertSame(24, $availability->slots()->count());

        $firstSlot = $availability->slots()->orderBy('start_time')->first();
        $this->assertSame('17:00:00', $firstSlot->start_time);
        $this->assertSame('17:05:00', $firstSlot->end_time);

        $lastSlot = $availability->slots()->orderBy('start_time', 'desc')->first();
        $this->assertSame('18:55:00', $lastSlot->start_time);
        $this->assertSame('19:00:00', $lastSlot->end_time);

        $this->assertTrue($availability->slots()->pluck('status')->every(fn ($s) => $s === SlotStatus::Available));
    }

    public function test_slot_count_matches_window_length_for_a_smaller_window(): void
    {
        $teacher = User::factory()->teacher()->create();
        $date = today()->addDay()->toDateString();

        $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '09:15',
        ]);

        $availability = Availability::where('teacher_id', $teacher->id)->firstOrFail();
        $this->assertSame(3, $availability->slots()->count());
    }

    public function test_availability_window_not_a_multiple_of_5_minutes_is_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();
        $date = today()->addDay()->toDateString();

        $response = $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '09:07',
        ]);

        $response->assertSessionHasErrors('end_time');
        $this->assertSame(0, Availability::where('teacher_id', $teacher->id)->count());
    }

    public function test_availability_in_the_past_is_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => today()->subDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_end_time_before_start_time_is_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => today()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '09:00',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_overlapping_availability_is_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();
        $date = today()->addDay()->toDateString();

        $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => $date, 'start_time' => '17:00', 'end_time' => '18:00',
        ]);

        $response = $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => $date, 'start_time' => '17:30', 'end_time' => '18:30',
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertSame(1, Availability::where('teacher_id', $teacher->id)->count());
    }

    public function test_non_overlapping_availability_on_same_day_is_allowed(): void
    {
        $teacher = User::factory()->teacher()->create();
        $date = today()->addDay()->toDateString();

        $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => $date, 'start_time' => '17:00', 'end_time' => '18:00',
        ]);

        $response = $this->actingAs($teacher)->post(route('teacher.availability.store'), [
            'date' => $date, 'start_time' => '18:00', 'end_time' => '19:00',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(2, Availability::where('teacher_id', $teacher->id)->count());
    }

    public function test_teacher_can_disable_an_available_slot(): void
    {
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Available,
        ]);

        $this->actingAs($teacher)->patch(route('teacher.availability.slots.toggle', $slot))
            ->assertRedirect(route('teacher.availability.show', $availability));

        $this->assertSame(SlotStatus::Disabled, $slot->fresh()->status);
    }

    public function test_teacher_can_re_enable_a_disabled_slot(): void
    {
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Disabled,
        ]);

        $this->actingAs($teacher)->patch(route('teacher.availability.slots.toggle', $slot));

        $this->assertSame(SlotStatus::Available, $slot->fresh()->status);
    }

    public function test_teacher_cannot_disable_a_booked_slot(): void
    {
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Booked,
        ]);

        $this->actingAs($teacher)->patch(route('teacher.availability.slots.toggle', $slot))
            ->assertSessionHasErrors('slot');

        $this->assertSame(SlotStatus::Booked, $slot->fresh()->status);
    }

    public function test_teacher_cannot_toggle_another_teachers_slot(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacherB, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacherB->id,
            'availability_id' => $availability->id,
        ]);

        $this->actingAs($teacherA)->patch(route('teacher.availability.slots.toggle', $slot))->assertForbidden();
    }

    public function test_teacher_can_remove_availability_with_no_bookings(): void
    {
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Available,
        ]);

        $this->actingAs($teacher)->delete(route('teacher.availability.destroy', $availability))
            ->assertRedirect(route('teacher.availability.index'));

        $this->assertDatabaseMissing('availability', ['id' => $availability->id]);
    }

    public function test_teacher_cannot_remove_availability_with_booked_slots(): void
    {
        $teacher = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'status' => SlotStatus::Booked,
        ]);

        $this->actingAs($teacher)->delete(route('teacher.availability.destroy', $availability))
            ->assertSessionHasErrors('availability');

        $this->assertDatabaseHas('availability', ['id' => $availability->id]);
    }

    public function test_teacher_can_remove_availability_whose_only_appointment_was_cancelled(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $availability = Availability::factory()->for($teacher, 'teacher')->create();
        $slot = AppointmentSlot::factory()->create([
            'teacher_id' => $teacher->id,
            'availability_id' => $availability->id,
            'date' => $availability->date,
            'start_time' => $availability->start_time,
            // Reverted to available after the guardian cancelled — nothing
            // is actually booked here any more.
            'status' => SlotStatus::Available,
        ]);
        $appointment = Appointment::factory()->cancelled()->create([
            'slot_id' => $slot->id,
            'teacher_id' => $teacher->id,
            'guardian_id' => $guardian->id,
            'child_id' => $child->id,
            'date' => $slot->date,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
        ]);

        $this->actingAs($teacher)->delete(route('teacher.availability.destroy', $availability))
            ->assertRedirect(route('teacher.availability.index'));

        $this->assertDatabaseMissing('availability', ['id' => $availability->id]);
        $this->assertDatabaseMissing('appointment_slots', ['id' => $slot->id]);

        // The cancelled appointment's own historical record survives, with
        // its date/time intact, even though the slot it once pointed to is
        // now gone.
        $appointment->refresh();
        $this->assertNull($appointment->slot_id);
        $this->assertSame($slot->date->toDateString(), $appointment->date->toDateString());
        $this->assertSame($slot->start_time, $appointment->start_time);
    }

    public function test_teacher_can_remove_several_such_availabilities_in_a_row(): void
    {
        $teacher = User::factory()->teacher()->create();
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $availabilities = collect(range(1, 3))->map(function (int $daysOut) use ($teacher, $guardian, $child) {
            $availability = Availability::factory()->for($teacher, 'teacher')->create([
                'date' => today()->addDays($daysOut)->toDateString(),
            ]);
            $slot = AppointmentSlot::factory()->create([
                'teacher_id' => $teacher->id,
                'availability_id' => $availability->id,
                'date' => $availability->date,
                'start_time' => $availability->start_time,
                'status' => SlotStatus::Available,
            ]);
            Appointment::factory()->cancelled()->create([
                'slot_id' => $slot->id,
                'teacher_id' => $teacher->id,
                'guardian_id' => $guardian->id,
                'child_id' => $child->id,
                'date' => $slot->date,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
            ]);

            return $availability;
        });

        foreach ($availabilities as $availability) {
            $this->actingAs($teacher)->delete(route('teacher.availability.destroy', $availability))
                ->assertRedirect(route('teacher.availability.index'));

            $this->assertDatabaseMissing('availability', ['id' => $availability->id]);
        }
    }

    public function test_teacher_cannot_remove_another_teachers_availability(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $availability = Availability::factory()->for($teacherB, 'teacher')->create();

        $this->actingAs($teacherA)->delete(route('teacher.availability.destroy', $availability))->assertForbidden();
    }

    public function test_teacher_only_sees_their_own_availability(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        Availability::factory()->for($teacherA, 'teacher')->create(['start_time' => '10:00:00', 'end_time' => '10:05:00']);
        Availability::factory()->for($teacherB, 'teacher')->create(['start_time' => '11:00:00', 'end_time' => '11:05:00']);

        $response = $this->actingAs($teacherA)->get(route('teacher.availability.index'));

        // Bare time strings like "10:00" also appear as options in the
        // create-availability time dropdown, so assert on the full rendered
        // range from the "upcoming availability" list instead.
        $response->assertSee('10:00–10:05');
        $response->assertDontSee('11:00–11:05');
    }
}
