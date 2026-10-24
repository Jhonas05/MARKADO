<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAssessmentEngineEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            config('assessment.routes_enabled'),
            503,
            'Assessment actions are not operationally enabled.',
        );

        return $next($request);
    }
}
