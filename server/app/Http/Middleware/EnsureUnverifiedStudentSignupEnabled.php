<?php

namespace App\Http\Middleware;

use App\Identity\Support\UnverifiedStudentSignupEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUnverifiedStudentSignupEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(UnverifiedStudentSignupEnvironment::availableFor($request), 404);

        return $next($request);
    }
}
