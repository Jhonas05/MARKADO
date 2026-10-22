<?php

namespace Tests\Feature;

use App\Assessment\Contracts\AnswerStore;
use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\DeadlinePlanner;
use App\Assessment\Contracts\PermutationPlanner;
use App\Assessment\Contracts\ResultReleaseManager;
use App\Assessment\Contracts\ScoreCalculator;
use App\Assessment\Contracts\TimerPlanner;
use App\Assessment\Support\AssessmentEngineCapabilities;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AssessmentEngineContractsTest extends TestCase
{
    public function test_policy_dependent_contracts_exist_but_are_not_activated(): void
    {
        $contracts = [
            AuthoritativeClock::class,
            DeadlinePlanner::class,
            TimerPlanner::class,
            AttemptSessionManager::class,
            PermutationPlanner::class,
            AnswerStore::class,
            AttemptFinalizer::class,
            ScoreCalculator::class,
            ResultReleaseManager::class,
        ];

        foreach ($contracts as $contract) {
            $this->assertTrue(interface_exists($contract));
            $this->assertFalse($this->app->bound($contract));
        }

        $capabilities = app(AssessmentEngineCapabilities::class)->toArray();

        foreach ($capabilities as $capability) {
            $this->assertFalse($capability['enabled']);
            $this->assertNotEmpty($capability['blockers']);
        }
    }

    public function test_no_attempt_start_answer_save_or_submission_route_is_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route): string => implode('|', $route->methods()).' '.$route->uri())
            ->implode("\n");

        $this->assertStringNotContainsString('student/quizzes/start', $routes);
        $this->assertStringNotContainsString('student/attempts', $routes);
        $this->assertStringNotContainsString('answers', $routes);
        $this->assertStringNotContainsString('submit', $routes);
    }
}
