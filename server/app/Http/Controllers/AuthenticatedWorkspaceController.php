<?php

namespace App\Http\Controllers;

use App\Enums\AccountRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthenticatedWorkspaceController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $route = match ($request->user()?->role) {
            AccountRole::Student => 'student.dashboard',
            AccountRole::Instructor => 'professor.quizzes.index',
            AccountRole::AcademicAdministrator => 'administrator.dashboard',
            default => null,
        };

        abort_if($route === null, 403);

        return redirect()->route($route);
    }
}
