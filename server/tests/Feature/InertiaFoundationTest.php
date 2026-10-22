<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaFoundationTest extends TestCase
{
    public function test_student_dashboard_is_served_through_inertia(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('student.firstName', 'Mika')
                ->where('quiz.course', 'BIO 104')
                ->has('metrics', 3)
                ->has('recentResults', 3)
                ->where('auth.user', null)
                ->missing('answerKey')
            );
    }

    public function test_foundation_route_uses_the_same_safe_dashboard_contract(): void
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
