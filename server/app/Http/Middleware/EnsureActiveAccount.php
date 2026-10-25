<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->account_status === AccountStatus::Active && $user->role !== null) {
            return $next($request);
        }

        $route = $user?->account_status === AccountStatus::Disabled
            ? 'account.disabled'
            : 'account.pending';

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new RedirectResponse(route($route));
    }
}
