<?php

namespace App\Http\Controllers;

use App\Enums\AccountRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedWorkspaceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $isInstructor = $request->user()?->role === AccountRole::Instructor;
        $isStudent = $request->user()?->role === AccountRole::Student;

        return Inertia::render('Auth/Workspace', [
            'foundation' => [
                'database' => config('database.default'),
                'persistence' => $isInstructor
                    ? 'Quiz drafts, immutable publication revisions, and assignment previews are PostgreSQL-backed.'
                    : 'Authentication and eligibility preflight are PostgreSQL-backed; student attempts remain disabled.',
                'policyStatus' => $isInstructor
                    ? 'Phase 5D publication active'
                    : 'Role-limited foundation',
                'quizAuthoringUrl' => $isInstructor ? route('professor.quizzes.index') : null,
                'quizJoinUrl' => $isStudent ? route('student.quizzes.join') : null,
            ],
        ]);
    }
}
