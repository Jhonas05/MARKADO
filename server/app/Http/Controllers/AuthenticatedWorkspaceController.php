<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedWorkspaceController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Auth/Workspace', [
            'foundation' => [
                'database' => config('database.default'),
                'persistence' => 'Authentication only; quiz records remain synthetic.',
                'policyStatus' => 'Phase 5B approval required',
            ],
        ]);
    }
}
