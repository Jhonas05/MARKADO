<?php

namespace App\Http\Middleware;

use App\Identity\Support\LocalRegistrationEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(LocalRegistrationEnvironment::enabled(), 404);
        if (! app()->environment('testing')) {
            abort_unless(in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1'], true)
                && in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
        }

        return $next($request);
    }
}
