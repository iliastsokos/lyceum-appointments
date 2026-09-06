<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guardian/teacher get a bottom tab bar (mobile) and a matching sm:+ top
     * link row, both driven by the same per-role tab list (see
     * AppServiceProvider's navTabs view composer) — its "Αρχική" tab must be
     * highlighted while on their own dashboard.
     */
    public function test_the_home_tab_is_highlighted_on_each_roles_own_dashboard(): void
    {
        $guardian = User::factory()->guardian()->create();
        $teacher = User::factory()->teacher()->create();

        foreach ([
            [$guardian, route('guardian.dashboard')],
            [$teacher, route('teacher.dashboard')],
        ] as [$user, $url]) {
            $response = $this->actingAs($user)->get($url);
            $response->assertSee('aria-current="page"', false);
            $response->assertSee('border-secondary', false);
        }
    }

    /**
     * Admin's own dashboard is already a hub linking every admin section,
     * so admin deliberately gets no persistent tab bar at all.
     */
    public function test_admin_has_no_bottom_tab_bar(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('aria-label="Κύρια πλοήγηση"', false);
    }
}
