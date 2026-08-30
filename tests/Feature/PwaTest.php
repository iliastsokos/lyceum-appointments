<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    /**
     * These are static files under public/, served directly by the web
     * server (or `php artisan serve`'s built-in router) before a request
     * ever reaches Laravel — there's no app route for them to hit, so
     * PHPUnit's HTTP test client can't exercise real delivery here. This
     * only checks the files ship with the app and are individually valid.
     */
    public function test_manifest_service_worker_and_offline_page_exist_and_are_valid(): void
    {
        $this->assertFileExists(public_path('manifest.json'));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));

        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);
        $this->assertSame('1ο ΓΕΛ Ραφήνας - Σύστημα Ραντεβού', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
    }

    public function test_landing_page_links_the_manifest_and_registers_the_service_worker(): void
    {
        $response = $this->get('/');

        $response->assertSee('rel="manifest" href="/manifest.json"', false);
        $response->assertSee('serviceWorker.register', false);
    }
}
