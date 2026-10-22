<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountRole
{
    /**
     * Restrict a route to one of the explicitly named account roles.
     *
     * Resource ownership and action-level authorization must still be enforced
     * by policies once the Phase 5B permission model is approved.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->value;

        abort_unless($role !== null && in_array($role, $roles, true), 403);

        return $next($request);
    }
}
