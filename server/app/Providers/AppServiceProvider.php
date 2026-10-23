<?php

namespace App\Providers;

use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Contracts\SchoolEligibilitySource;
use App\Assessment\Support\DatabaseQuizEligibilityGate;
use App\Assessment\Support\DatabaseSchoolEligibilitySource;
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
