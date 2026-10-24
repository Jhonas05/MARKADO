<?php

namespace App\Http\Controllers;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Support\AssessmentEngineCapabilities;
use App\Assessment\Support\QuizPreflightResolver;
use App\Http\Requests\JoinQuizRequest;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentQuizJoinController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Student/Quizzes/Join', [
            'policyNotice' => config('assessment.routes_enabled')
                ? 'Quiz information and Start require a published quiz plus a verified, active school assignment.'
                : 'Quiz information is available only for published quizzes assigned through verified school records. Starting an attempt remains operationally disabled.',
        ]);
    }

    public function store(JoinQuizRequest $request): RedirectResponse
    {
        return to_route('student.quizzes.preflight.show', [
            'code' => $request->validated('code'),
        ]);
    }

    public function show(
        Request $request,
        string $code,
        QuizPreflightResolver $resolver,
        AssessmentEngineCapabilities $capabilities,
        AuthoritativeClock $clock,
    ): Response {
        $preflight = $resolver->resolve($request->user(), $code);
        $payload = $preflight->toArray();
        $opensAt = isset($payload['quiz']['opensAt']) ? new DateTimeImmutable($payload['quiz']['opensAt']) : null;
        $closesAt = isset($payload['quiz']['closesAt']) ? new DateTimeImmutable($payload['quiz']['closesAt']) : null;
        $now = $clock->now();
        $withinStartWindow = $closesAt instanceof DateTimeImmutable
            && $now < $closesAt
            && (! $opensAt instanceof DateTimeImmutable || $now >= $opensAt);
        $payload['canStart'] = $payload['state'] === 'information_available'
            && (bool) config('assessment.routes_enabled')
            && $withinStartWindow;
        $payload['startMessage'] = match (true) {
            $payload['state'] !== 'information_available' => 'Start is unavailable.',
            ! config('assessment.routes_enabled') => 'Assessment Start remains operationally disabled.',
            ! $closesAt instanceof DateTimeImmutable => 'The Professor must configure a valid availability window before Start.',
            $opensAt instanceof DateTimeImmutable && $now < $opensAt => 'This quiz has not opened yet.',
            $now >= $closesAt => 'This quiz is closed for new attempts.',
            default => 'Start is a separate server-authorized action. Timers begin only after the server accepts it.',
        };

        return Inertia::render('Student/Quizzes/Preflight', [
            'preflight' => $payload,
            'capabilities' => $capabilities->toArray(),
        ]);
    }
}
