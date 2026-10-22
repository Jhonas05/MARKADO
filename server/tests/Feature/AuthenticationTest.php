<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
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

    public function test_login_screen_exposes_no_registration_action(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('registration.available', false)
                ->where('auth.user', null)
            );

        $this->assertFalse(Route::has('register'));
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
        ])->assertRedirect('/workspace');

        $this->assertAuthenticatedAs($user);

        $this->get('/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Workspace')
                ->where('auth.user.id', $user->id)
                ->where('auth.user.email', $user->email)
                ->where('auth.user.role', AccountRole::Student->value)
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
            );
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
