<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The "Πίνακας Ελέγχου" nav link checks request()->routeIs('dashboard')
     * for its active/highlighted state, but every role's actual dashboard
     * route is prefixed (guardian.dashboard, teacher.dashboard,
     * admin.dashboard) — routeIs('dashboard') alone never matches any page
     * a user actually lands on, so the link never highlighted.
     */
    public function test_dashboard_nav_link_is_highlighted_on_each_roles_own_dashboard(): void
    {
        $guardian = User::factory()->guardian()->create();
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        foreach ([
            [$guardian, route('guardian.dashboard')],
            [$teacher, route('teacher.dashboard')],
            [$admin, route('admin.dashboard')],
        ] as [$user, $url]) {
            $response = $this->actingAs($user)->get($url);
            $response->assertSee('border-[#f2952b]', false);
        }
    }
}
