<?php

namespace App\Providers;

use App\Assessment\Contracts\AnswerStore;
use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Contracts\SchoolEligibilitySource;
use App\Assessment\Support\AttemptFinalizationCoordinator;
use App\Assessment\Support\DatabaseAnswerStore;
use App\Assessment\Support\DatabaseAttemptSessionManager;
use App\Assessment\Support\DatabaseQuizEligibilityGate;
use App\Assessment\Support\DatabaseSchoolEligibilitySource;
use App\Assessment\Support\SystemAuthoritativeClock;
use App\Models\Quiz;
use App\Models\QuizRevision;
use App\Policies\QuizPolicy;
use App\Policies\QuizRevisionPolicy;
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
        $this->app->bind(SchoolEligibilitySource::class, DatabaseSchoolEligibilitySource::class);
        $this->app->bind(QuizEligibilityGate::class, DatabaseQuizEligibilityGate::class);
        $this->app->bind(AuthoritativeClock::class, SystemAuthoritativeClock::class);
        $this->app->bind(AttemptFinalizer::class, AttemptFinalizationCoordinator::class);
        $this->app->bind(AttemptSessionManager::class, DatabaseAttemptSessionManager::class);
        $this->app->bind(AnswerStore::class, DatabaseAnswerStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(QuizRevision::class, QuizRevisionPolicy::class);

        RateLimiter::for('quiz-join', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
