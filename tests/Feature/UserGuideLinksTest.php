<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGuideLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_pdfs_exist(): void
    {
        $this->assertFileExists(public_path('user-guides/odigos-kidemona.pdf'));
        $this->assertFileExists(public_path('user-guides/odigos-ekpaideftikou.pdf'));
    }

    public function test_guardian_dashboard_links_to_the_guardian_guide(): void
    {
        $guardian = User::factory()->guardian()->create();

        $response = $this->actingAs($guardian)->get(route('guardian.dashboard'));

        $response->assertSee('/user-guides/odigos-kidemona.pdf', false);
        $response->assertDontSee('/user-guides/odigos-ekpaideftikou.pdf', false);
    }

    public function test_teacher_dashboard_links_to_the_teacher_guide(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertSee('/user-guides/odigos-ekpaideftikou.pdf', false);
        $response->assertDontSee('/user-guides/odigos-kidemona.pdf', false);
    }

    public function test_welcome_page_links_to_both_guides(): void
    {
        $response = $this->get('/');

        $response->assertSee('/user-guides/odigos-kidemona.pdf', false);
        $response->assertSee('/user-guides/odigos-ekpaideftikou.pdf', false);
    }
}
