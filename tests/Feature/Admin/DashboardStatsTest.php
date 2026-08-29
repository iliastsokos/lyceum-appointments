<?php

namespace Tests\Feature\Admin;

use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_correct_counts(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->count(2)->create();
        $guardians = User::factory()->guardian()->count(3)->create();
        Child::factory()->for($guardians[0], 'guardian')->count(2)->create();
        Child::factory()->for($guardians[1], 'guardian')->count(1)->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // Teachers: 2, Guardians: 3, Students: 3 — each appears as a distinct stat tile.
        $response->assertSeeInOrder(['Εκπαιδευτικοί', '2', 'Κηδεμόνες', '3', 'Μαθητές', '3']);
    }
}
