<?php

namespace App\Http\Controllers;

use App\Identity\Support\RegistrationEnvironment;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'accountPolicies' => [
                'publicStudentRegistration' => RegistrationEnvironment::enabled(),
                'publicStaffRegistration' => false,
                'passwordRecovery' => false,
            ],
        ]);
    }
}
