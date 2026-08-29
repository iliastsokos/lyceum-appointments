<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('1ο ΓΕΛ Ραφήνας');
        $response->assertSee('Ραντεβού');
    }

    public function test_landing_page_links_to_login_and_registration(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('login'), false);
        $response->assertSee(route('register'), false);
    }
}
