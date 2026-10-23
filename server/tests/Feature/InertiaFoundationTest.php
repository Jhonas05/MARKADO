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

    public function test_foundation_route_retains_the_safe_synthetic_fixture(): void
    {
        $this->get('/foundation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('foundation.persistence', 'No quiz data is persisted yet')
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
}
