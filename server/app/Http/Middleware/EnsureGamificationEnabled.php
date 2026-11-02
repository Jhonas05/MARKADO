<?php

namespace App\Http\Middleware;

use App\Gamification\Support\GamificationEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGamificationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            GamificationEnvironment::enabled(),
            503,
            'Character rewards are not available for this account.',
        );

        return $next($request);
    }
}
