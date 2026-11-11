<?php

namespace App\Http\Middleware;

use App\Identity\Support\HostedPresentationRegistrationEnvironment;
use App\Identity\Support\LocalRegistrationEnvironment;
use App\Identity\Support\ProductionRegistrationEnvironment;
use App\Identity\Support\RegistrationEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(RegistrationEnvironment::enabled(), 404);

        if (! app()->environment('testing') && LocalRegistrationEnvironment::enabled()) {
            abort_unless(in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1'], true)
                && in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
        }

        if (ProductionRegistrationEnvironment::enabled()) {
            abort_unless($request->isSecure(), 404);
        }

        if (HostedPresentationRegistrationEnvironment::enabled()) {
            abort_unless($request->isSecure()
                && hash_equals(
                    (string) parse_url((string) config('app.url'), PHP_URL_HOST),
                    $request->getHost(),
                ), 404);
        }

        return $next($request);
    }
}
