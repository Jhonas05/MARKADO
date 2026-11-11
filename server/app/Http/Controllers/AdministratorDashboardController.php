<?php

namespace App\Http\Controllers;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Gamification\Support\GamificationEnvironment;
use App\Identity\Support\ProductionRegistrationEnvironment;
use App\Identity\Support\RegistrationEnvironment;
use App\Models\InstitutionalEmailVerificationChallenge;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdministratorDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Administrator/Dashboard', [
            'summary' => [
                'activeStudents' => User::query()->where('role', AccountRole::Student)->where('account_status', AccountStatus::Active)->count(),
                'activeProfessors' => User::query()->where('role', AccountRole::Instructor)->where('account_status', AccountStatus::Active)->count(),
                'pendingStudents' => User::query()->where('role', AccountRole::Student)->where('account_status', AccountStatus::Pending)->count(),
                'publishedQuizzes' => Quiz::query()->where('status', 'published')->count(),
                'activeAttempts' => QuizAttempt::query()->where('status', 'in_progress')->count(),
                'submittedAttempts' => QuizAttempt::query()->where('status', 'submitted')->count(),
                'pendingEmailChallenges' => InstitutionalEmailVerificationChallenge::query()
                    ->whereNull('consumed_at')->whereNull('revoked_at')->where('expires_at', '>', now())->count(),
            ],
            'operations' => [
                'registrationAvailable' => RegistrationEnvironment::enabled(),
                'productionRegistrationReady' => ProductionRegistrationEnvironment::enabled(),
                'assessmentRoutesEnabled' => (bool) config('assessment.routes_enabled'),
                'expirationEnabled' => (bool) config('assessment.expiration_enabled'),
                'gamificationEnabled' => GamificationEnvironment::enabled(),
            ],
            'boundaries' => [
                'accountProvisioning' => 'Account provisioning is unavailable in this workspace',
                'assessmentOverrides' => 'Assessment overrides are unavailable in this workspace',
                'resultAccess' => 'Permission and resource scoped only',
            ],
        ]);
    }
}
