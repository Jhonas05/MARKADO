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

        return Inertia::render('Auth/Workspace', [
            'foundation' => [
                'database' => config('database.default'),
                'persistence' => $isInstructor
                    ? 'Private quiz drafts and approved question formats are PostgreSQL-backed.'
                    : 'Authentication is PostgreSQL-backed; prototype student attempts remain disabled.',
                'policyStatus' => $isInstructor
                    ? 'Phase 5B authoring active'
                    : 'Role-limited foundation',
                'quizAuthoringUrl' => $isInstructor ? route('professor.quizzes.index') : null,
            ],
        ]);
    }
}
