<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_route_shows_friendly_404_page(): void
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertNotFound();
        $response->assertSee('Page not found');
        $response->assertDontSee('vendor/laravel', false);
        $response->assertDontSee('Stack trace', false);
    }

    public function test_cross_role_access_shows_friendly_403_page_not_a_stack_trace(): void
    {
        $guardian = User::factory()->guardian()->create();

        $response = $this->actingAs($guardian)->get(route('admin.dashboard'));

        $response->assertForbidden();
        $response->assertSee('Access denied');
        $response->assertDontSee('Stack trace', false);
    }

    public function test_500_error_view_never_leaks_exception_details(): void
    {
        $html = view('errors.500', ['exception' => new \Exception('super secret internal detail')])->render();

        $this->assertStringNotContainsString('super secret internal detail', $html);
        $this->assertStringContainsString('Something went wrong', $html);
    }

    public function test_an_uncaught_exception_shows_the_friendly_500_page_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test-throws', function () {
            throw new \RuntimeException('super secret internal detail');
        });

        $response = $this->get('/__test-throws');

        $response->assertServerError();
        $response->assertSee('Something went wrong');
        $response->assertDontSee('super secret internal detail', false);
        $response->assertDontSee('Stack trace', false);
    }

    public function test_error_pages_render_without_the_vite_asset_pipeline(): void
    {
        // Error pages must not depend on @vite/the compiled manifest — if the
        // build itself is broken, the error page is exactly what must still work.
        foreach (['403', '404', '419', '429', '500', '503'] as $code) {
            $html = view("errors.{$code}", ['exception' => new \Exception('x')])->render();
            $this->assertStringNotContainsString('@vite', $html);
            $this->assertStringContainsString('Go to homepage', $html);
        }
    }
}
