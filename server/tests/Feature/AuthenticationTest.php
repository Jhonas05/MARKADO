<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_gated_registration_surfaces_are_available_without_registration_mutation(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('registration.available', false)
                ->where('auth.user', null)
            );

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('registration.available', false)
            );

        $this->assertTrue(Route::has('register'));
        $this->assertFalse(collect(Route::getRoutes())->contains(
            fn ($route) => $route->uri() === 'register' && in_array('POST', $route->methods(), true),
        ));
    }

    public function test_provisioned_user_can_log_in_and_receive_only_safe_props(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('valid-test-credential'),
            'role' => AccountRole::Student,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'valid-test-credential',
        ])->assertRedirect('/student/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->get('/student/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Workspace')
                ->where('auth.user.id', $user->id)
                ->where('auth.user.role', AccountRole::Student->value)
                ->missing('auth.user.email')
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
            );
    }

    public function test_client_supplied_role_cannot_change_the_server_assigned_destination(): void
    {
        $student = User::factory()->create([
            'password' => Hash::make('valid-test-credential'),
            'role' => AccountRole::Student,
        ]);

        $this->post('/login', [
            'email' => $student->email,
            'password' => 'valid-test-credential',
            'role' => AccountRole::AcademicAdministrator->value,
            'account_status' => AccountStatus::Active->value,
        ])->assertRedirect('/student/dashboard');

        $this->assertAuthenticatedAs($student);
    }

    public function test_each_server_stored_role_receives_its_own_destination(): void
    {
        $destinations = [
            AccountRole::Student->value => '/student/dashboard',
            AccountRole::Instructor->value => '/professor/quizzes',
            AccountRole::AcademicAdministrator->value => '/admin/dashboard',
        ];

        foreach ($destinations as $role => $destination) {
            $user = User::factory()->create([
                'email' => "{$role}@example.test",
                'password' => Hash::make('valid-test-credential'),
                'role' => $role,
            ]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'valid-test-credential',
            ])->assertRedirect($destination);

            $this->delete('/logout');
        }
    }

    public function test_pending_disabled_and_unassigned_accounts_never_receive_a_workspace_session(): void
    {
        $cases = [
            ['status' => AccountStatus::Pending, 'role' => AccountRole::Student, 'destination' => '/account/pending'],
            ['status' => AccountStatus::Disabled, 'role' => AccountRole::Student, 'destination' => '/account/disabled'],
            ['status' => AccountStatus::Active, 'role' => null, 'destination' => '/account/pending'],
        ];

        foreach ($cases as $index => $case) {
            $user = User::factory()->create([
                'email' => "restricted-{$index}@example.test",
                'password' => Hash::make('valid-test-credential'),
                'role' => $case['role'],
                'account_status' => $case['status'],
            ]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'valid-test-credential',
            ])->assertRedirect($case['destination']);

            $this->assertGuest();
        }
    }

    public function test_account_status_changes_revoke_an_existing_workspace_session_on_the_next_request(): void
    {
        foreach ([
            AccountRole::Student->value => '/student/dashboard',
            AccountRole::Instructor->value => '/professor/quizzes',
            AccountRole::AcademicAdministrator->value => '/admin/dashboard',
        ] as $role => $protectedPath) {
            foreach ([
                AccountStatus::Pending->value => '/account/pending',
                AccountStatus::Disabled->value => '/account/disabled',
            ] as $status => $destination) {
                $user = User::factory()->create([
                    'role' => $role,
                    'account_status' => AccountStatus::Active,
                ]);

                $this->actingAs($user);
                $user->update(['account_status' => $status]);

                $this->get($protectedPath)->assertRedirect($destination);
                $this->assertGuest();
            }
        }
    }

    public function test_all_authenticated_workspaces_require_request_time_active_account_checks(): void
    {
        foreach ([
            'workspace',
            'student.dashboard',
            'student.quizzes.join',
            'student.attempts.show',
            'professor.quizzes.index',
            'administrator.dashboard',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $middleware = $route->gatherMiddleware();

            $this->assertContains('active-account', $middleware, "{$routeName} must recheck account status.");
        }
    }

    public function test_role_routes_cannot_be_escalated_by_changing_the_url(): void
    {
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $instructor = User::factory()->create(['role' => AccountRole::Instructor]);
        $administrator = User::factory()->create(['role' => AccountRole::AcademicAdministrator]);

        $this->actingAs($student)->get('/professor/quizzes')->assertForbidden();
        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($instructor)->get('/student/dashboard')->assertForbidden();
        $this->actingAs($administrator)->get('/student/dashboard')->assertForbidden();
    }

    public function test_invalid_credentials_return_a_generic_error(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('valid-test-credential'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'not-the-credential',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $credentials = [
            'email' => 'missing-account@example.test',
            'password' => 'not-the-credential',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', $credentials)->assertSessionHasErrors('email');
        }

        $this->post('/login', $credentials)->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'too many',
            strtolower(session('errors')->first('email')),
        );
    }

    public function test_guest_is_redirected_and_authenticated_user_can_log_out(): void
    {
        $this->get('/workspace')->assertRedirect('/login');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_login_post_route_is_inside_the_csrf_protected_web_group(): void
    {
        $route = Route::getRoutes()->getByName('login.store');
        $middleware = $this->app['router']->gatherRouteMiddleware($route);

        $this->assertContains(ValidateCsrfToken::class, $middleware);
    }
}
