<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_admin_can_update_their_own_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $admin->refresh();

        $this->assertSame('Test', $admin->first_name);
        $this->assertSame('User', $admin->last_name);
        $this->assertSame('test@example.com', $admin->email);
        $this->assertNull($admin->email_verified_at);
    }

    public function test_guardian_cannot_change_their_name_via_profile(): void
    {
        $guardian = User::factory()->guardian()->create(['first_name' => 'Original', 'last_name' => 'Name']);

        $response = $this
            ->actingAs($guardian)
            ->patch('/profile', [
                'first_name' => 'Tampered',
                'last_name' => 'Tampered',
                'phone' => '6900000000',
                'email' => $guardian->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $guardian->refresh();

        $this->assertSame('Original', $guardian->first_name);
        $this->assertSame('Name', $guardian->last_name);
        $this->assertSame('6900000000', $guardian->phone);
    }

    public function test_teacher_cannot_change_their_name_via_profile(): void
    {
        $teacher = User::factory()->teacher()->create(['first_name' => 'Original', 'last_name' => 'Name']);

        $response = $this
            ->actingAs($teacher)
            ->patch('/profile', [
                'first_name' => 'Tampered',
                'last_name' => 'Tampered',
                'phone' => '6900000000',
                'email' => $teacher->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $teacher->refresh();

        $this->assertSame('Original', $teacher->first_name);
        $this->assertSame('Name', $teacher->last_name);
        $this->assertSame('6900000000', $teacher->phone);
    }

    public function test_guardian_can_update_their_phone_and_email(): void
    {
        $guardian = User::factory()->guardian()->create();

        $response = $this
            ->actingAs($guardian)
            ->patch('/profile', [
                'phone' => '6912345678',
                'email' => 'new-email@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $guardian->refresh();

        $this->assertSame('6912345678', $guardian->phone);
        $this->assertSame('new-email@example.com', $guardian->email);
        $this->assertNull($guardian->email_verified_at);
    }

    public function test_name_fields_are_not_shown_to_guardians_and_teachers(): void
    {
        $guardian = User::factory()->guardian()->create();

        $response = $this->actingAs($guardian)->get('/profile');

        $response->assertDontSee('name="first_name"', false);
        $response->assertDontSee('name="last_name"', false);
        $response->assertSee(__('Για αλλαγή ονόματος ή επωνύμου επικοινωνήστε με τη Διεύθυνση του σχολείου.'));
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
