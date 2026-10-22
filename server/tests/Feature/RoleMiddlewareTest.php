<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Http\Middleware\EnsureAccountRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_matching_role_passes_the_foundation_gate(): void
    {
        $request = Request::create('/role-check');
        $request->setUserResolver(fn () => new User([
            'role' => AccountRole::Instructor,
        ]));

        $response = (new EnsureAccountRole)->handle(
            $request,
            fn () => response()->noContent(),
            AccountRole::Instructor->value,
        );

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_non_matching_role_is_forbidden(): void
    {
        $request = Request::create('/role-check');
        $request->setUserResolver(fn () => new User([
            'role' => AccountRole::Student,
        ]));

        try {
            (new EnsureAccountRole)->handle(
                $request,
                fn () => response()->noContent(),
                AccountRole::Instructor->value,
            );

            $this->fail('A mismatched role must not pass the role gate.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
