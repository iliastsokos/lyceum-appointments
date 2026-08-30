<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_form_is_visible_when_no_users_exist(): void
    {
        $this->get(route('setup-admin.create'))->assertOk();
    }

    public function test_submitting_the_form_creates_an_admin_and_logs_them_in(): void
    {
        $response = $this->post(route('setup-admin.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'email' => 'admin@example.gr',
            'password' => '1234',
            'password_confirmation' => '1234',
        ]);

        $response->assertRedirect(route('dashboard'));

        $admin = User::where('email', 'admin@example.gr')->firstOrFail();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertFalse($admin->must_change_password);
        $this->assertTrue(Hash::check('1234', $admin->password));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_setup_form_404s_once_a_user_already_exists(): void
    {
        User::factory()->admin()->create();

        $this->get(route('setup-admin.create'))->assertNotFound();
    }

    public function test_submitting_the_form_404s_once_a_user_already_exists(): void
    {
        User::factory()->admin()->create();

        $response = $this->post(route('setup-admin.store'), [
            'first_name' => 'Second',
            'last_name' => 'Admin',
            'email' => 'second@example.gr',
            'password' => '1234',
            'password_confirmation' => '1234',
        ]);

        $response->assertNotFound();
        $this->assertSame(1, User::count());
    }

    public function test_password_must_meet_the_four_character_minimum(): void
    {
        $response = $this->post(route('setup-admin.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'email' => 'admin@example.gr',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertSame(0, User::count());
    }
}
