<?php

namespace App\Providers;

use App\Assessment\Contracts\AnswerStore;
use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Contracts\ResultReleaseManager;
use App\Assessment\Contracts\SchoolEligibilitySource;
use App\Assessment\Contracts\ScoreCalculator;
use App\Assessment\Support\AttemptFinalizationCoordinator;
use App\Assessment\Support\DatabaseAnswerStore;
use App\Assessment\Support\DatabaseAttemptSessionManager;
use App\Assessment\Support\DatabaseQuizEligibilityGate;
use App\Assessment\Support\DatabaseResultReleaseManager;
use App\Assessment\Support\DatabaseSchoolEligibilitySource;
use App\Assessment\Support\DatabaseScoreCalculator;
use App\Assessment\Support\SystemAuthoritativeClock;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Contracts\InstitutionalMailboxProof;
use App\Identity\Contracts\RegistrationClaimVerifier;
use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Contracts\StudentActivationService;
use App\Identity\Contracts\StudentIdentitySource;
use App\Identity\Support\ConfiguredInstitutionalEmailPolicy;
use App\Identity\Support\DatabaseInstitutionalMailboxProof;
use App\Identity\Support\DatabaseRegistrationClaimVerifier;
use App\Identity\Support\DatabaseStudentActivationService;
use App\Identity\Support\DatabaseStudentIdentitySource;
use App\Identity\Support\DefaultDenyStudentActivationSecurityGate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizRevision;
use App\Models\SchoolStudentRecord;
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuizPolicy;
use App\Policies\QuizRevisionPolicy;
use App\Policies\SchoolStudentRecordPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InstitutionalEmailPolicy::class, ConfiguredInstitutionalEmailPolicy::class);
        $this->app->bind(InstitutionalMailboxProof::class, DatabaseInstitutionalMailboxProof::class);
        $this->app->bind(RegistrationClaimVerifier::class, DatabaseRegistrationClaimVerifier::class);
        $this->app->bind(StudentActivationSecurityGate::class, DefaultDenyStudentActivationSecurityGate::class);
        $this->app->bind(StudentActivationService::class, DatabaseStudentActivationService::class);
        $this->app->bind(StudentIdentitySource::class, DatabaseStudentIdentitySource::class);
        $this->app->bind(SchoolEligibilitySource::class, DatabaseSchoolEligibilitySource::class);
        $this->app->bind(QuizEligibilityGate::class, DatabaseQuizEligibilityGate::class);
        $this->app->bind(AuthoritativeClock::class, SystemAuthoritativeClock::class);
        $this->app->bind(AttemptFinalizer::class, AttemptFinalizationCoordinator::class);
        $this->app->bind(AttemptSessionManager::class, DatabaseAttemptSessionManager::class);
        $this->app->bind(AnswerStore::class, DatabaseAnswerStore::class);
        $this->app->bind(ScoreCalculator::class, DatabaseScoreCalculator::class);
        $this->app->bind(ResultReleaseManager::class, DatabaseResultReleaseManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);
        Gate::policy(QuizRevision::class, QuizRevisionPolicy::class);
        Gate::policy(SchoolStudentRecord::class, SchoolStudentRecordPolicy::class);

        RateLimiter::for('quiz-join', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
