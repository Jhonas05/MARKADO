<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AdministratorDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Administrator/Dashboard', [
            'boundaries' => [
                'accountProvisioning' => 'Account provisioning is unavailable in this workspace',
                'assessmentOverrides' => 'Assessment overrides are unavailable in this workspace',
                'resultAccess' => 'Permission and resource scoped only',
            ],
        ]);
    }
}
