<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaFoundationTest extends TestCase
{
    public function test_public_entry_is_a_production_style_inertia_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('accountPolicies.publicStudentRegistration', false)
                ->where('accountPolicies.publicStaffRegistration', false)
                ->where('accountPolicies.passwordRecovery', false)
                ->where('auth.user', null)
            );
    }

    public function test_public_foundation_demo_is_not_routed(): void
    {
        $this->get('/foundation')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ErrorPage')
                ->where('status', 404)
            );
    }

    public function test_unknown_routes_render_the_inertia_fallback(): void
    {
        $this->get('/not-a-real-markado-route')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ErrorPage')
                ->where('status', 404)
            );
    }

    public function test_postgresql_is_the_application_default(): void
    {
        $example = file_get_contents(base_path('.env.example'));
        $databaseConfig = file_get_contents(config_path('database.php'));

        $this->assertStringContainsString('DB_CONNECTION=pgsql', $example);
        $this->assertStringContainsString("env('DB_CONNECTION', 'pgsql')", $databaseConfig);
        $this->assertContains(config('database.default'), ['pgsql', 'sqlite']);
    }

    public function test_official_brand_assets_and_browser_metadata_are_available(): void
    {
        $canonical = public_path('branding/markado-logo.png');
        $wordmark = public_path('branding/markado-logo-transparent.png');
        $symbol = public_path('branding/markado-symbol.png');

        $this->assertFileExists($canonical);
        $this->assertFileExists($wordmark);
        $this->assertFileExists($symbol);
        $this->assertSame([1672, 941], array_slice(getimagesize($canonical), 0, 2));
        $this->assertSame([1561, 272], array_slice(getimagesize($wordmark), 0, 2));
        $this->assertSame([320, 320], array_slice(getimagesize($symbol), 0, 2));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));

        $this->get('/')
            ->assertOk()
            ->assertSee('content="MARKADO"', false)
            ->assertSee('href="/favicon.ico"', false)
            ->assertSee('href="/branding/favicon-32x32.png"', false)
            ->assertSee('href="/branding/apple-touch-icon.png"', false);
    }
}
