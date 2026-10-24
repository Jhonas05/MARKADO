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
    public function test_approved_implementation_contracts_are_bound_behind_disabled_routes(): void
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
        }

        $this->assertTrue($this->app->bound(AuthoritativeClock::class));
        $this->assertTrue($this->app->bound(AttemptFinalizer::class));
        $this->assertTrue($this->app->bound(AttemptSessionManager::class));
        $this->assertTrue($this->app->bound(AnswerStore::class));
        $this->assertTrue($this->app->bound(ScoreCalculator::class));
        $this->assertTrue($this->app->bound(ResultReleaseManager::class));

        foreach ([DeadlinePlanner::class, TimerPlanner::class, PermutationPlanner::class] as $contract) {
            $this->assertFalse($this->app->bound($contract));
        }

        $capabilities = app(AssessmentEngineCapabilities::class)->toArray();

        foreach ($capabilities as $capability) {
            $this->assertFalse($capability['routeEnabled']);
            $this->assertNotEmpty($capability['blockers']);
        }
    }

    public function test_attempt_routes_are_registered_only_behind_the_server_activation_gate(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route): string => implode('|', $route->methods()).' '.$route->uri())
            ->implode("\n");

        $this->assertStringContainsString('student/attempts/{attempt}/answers', $routes);
        $this->assertStringContainsString('student/attempts/{attempt}/submit', $routes);

        foreach ([
            'student.quizzes.attempts.store',
            'student.attempts.show',
            'student.attempts.answers.store',
            'student.attempts.advance',
            'student.attempts.submit',
            'professor.quizzes.results.index',
            'professor.quizzes.results.release',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('assessment-enabled', $route->gatherMiddleware());
        }
    }
}
