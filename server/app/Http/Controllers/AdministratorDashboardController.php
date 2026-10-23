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
                'accountProvisioning' => 'Disabled pending D15 authorization details',
                'assessmentOverrides' => 'Disabled pending D23 authorization details',
                'resultAccess' => 'Permission and resource scoped only',
            ],
        ]);
    }
}
