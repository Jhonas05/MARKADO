<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'registration' => [
                'available' => false,
                'message' => 'Account registration and institutional verification are awaiting approval.',
            ],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if ($user->account_status !== AccountStatus::Active || $user->role === null) {
            $route = $user->account_status === AccountStatus::Disabled
                ? 'account.disabled'
                : 'account.pending';

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($route);
        }

        $request->session()->regenerate();

        return redirect()->route(match ($user->role) {
            AccountRole::Student => 'student.dashboard',
            AccountRole::Instructor => 'professor.quizzes.index',
            AccountRole::AcademicAdministrator => 'administrator.dashboard',
        });
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
