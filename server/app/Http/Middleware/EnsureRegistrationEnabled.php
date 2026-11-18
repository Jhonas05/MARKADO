<?php

namespace App\Http\Middleware;

use App\Identity\Support\RegistrationEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(RegistrationEnvironment::availableFor($request), 404);

        return $next($request);
    }
}
