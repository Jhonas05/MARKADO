<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Identity\Support\RegistrationEnvironment;
use App\Identity\Support\UnverifiedStudentSignupEnvironment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'registration' => [
                'available' => Route::has('registration.store') && (RegistrationEnvironment::availableFor($request) || UnverifiedStudentSignupEnvironment::availableFor($request)),
                'message' => 'Student onboarding requires a verified institutional mailbox and matching trusted roster identity.',
            ],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if ($user->role === AccountRole::Student && $user->account_status === AccountStatus::Pending
            && Route::has('registration.verification') && RegistrationEnvironment::availableFor($request)) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->put('registration_user_id', $user->id);

            return redirect()->route('registration.verification');
        }

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
