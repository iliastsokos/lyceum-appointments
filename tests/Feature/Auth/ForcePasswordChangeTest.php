<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_must_change_password_is_redirected_from_dashboard(): void
    {
        $guardian = User::factory()->guardian()->mustChangePassword()->create();

        $this->actingAs($guardian)->get(route('guardian.dashboard'))
            ->assertRedirect(route('password.force-change'));
    }

    public function test_user_can_set_a_new_password_and_is_no_longer_forced(): void
    {
        $guardian = User::factory()->guardian()->mustChangePassword()->create();

        $response = $this->actingAs($guardian)->put(route('password.force-change.update'), [
            'password' => 'ANewSecurePass123',
            'password_confirmation' => 'ANewSecurePass123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $guardian->refresh();
        $this->assertFalse($guardian->must_change_password);
        $this->assertTrue(Hash::check('ANewSecurePass123', $guardian->password));

        // No longer forced onto the change-password screen.
        $this->actingAs($guardian)->get(route('guardian.dashboard'))->assertOk();
    }

    public function test_user_without_flag_is_not_redirected(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->get(route('guardian.dashboard'))->assertOk();
    }
}
