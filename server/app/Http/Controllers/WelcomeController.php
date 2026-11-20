<?php

namespace App\Http\Controllers;

use App\Identity\Support\RegistrationEnvironment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Welcome', [
            'accountPolicies' => [
                'publicStudentRegistration' => RegistrationEnvironment::canStart($request),
                'studentClaimSignup' => RegistrationEnvironment::claimSignupEnabled(),
                'publicStaffRegistration' => false,
                'passwordRecovery' => false,
            ],
        ]);
    }
}
