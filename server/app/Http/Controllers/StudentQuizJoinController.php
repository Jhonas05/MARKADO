<?php

namespace App\Http\Controllers;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Support\QuizPreflightResolver;
use App\Http\Requests\JoinQuizRequest;
use App\Models\QuizAttempt;
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
                ? 'You can continue only when the quiz is published and assigned to your verified school account.'
                : 'You can review only published quizzes assigned to your verified school account. Starting quizzes is not available right now.',
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
        AuthoritativeClock $clock,
    ): Response {
        $preflight = $resolver->resolve($request->user(), $code);
        $payload = $preflight->toArray();
        $existingAttempt = $payload['state'] === 'information_available' && config('assessment.routes_enabled')
            ? QuizAttempt::query()->where('quiz_id', $payload['quiz']['id'])->where('student_id', $request->user()->id)->first(['id', 'status'])
            : null;
        $payload['attempt'] = $existingAttempt === null ? null : [
            'url' => route('student.attempts.show', $existingAttempt),
            'status' => $existingAttempt->status,
        ];
        $opensAt = isset($payload['quiz']['opensAt']) ? new DateTimeImmutable($payload['quiz']['opensAt']) : null;
        $closesAt = isset($payload['quiz']['closesAt']) ? new DateTimeImmutable($payload['quiz']['closesAt']) : null;
        $now = $clock->now();
        $withinStartWindow = $closesAt instanceof DateTimeImmutable
            && $now < $closesAt
            && (! $opensAt instanceof DateTimeImmutable || $now >= $opensAt);
        $payload['canStart'] = $payload['state'] === 'information_available'
            && (bool) config('assessment.routes_enabled')
            && $existingAttempt === null
            && $withinStartWindow;
        $payload['startMessage'] = match (true) {
            $payload['state'] !== 'information_available' => 'Start is unavailable.',
            ! config('assessment.routes_enabled') => 'Starting quizzes is not available right now.',
            $existingAttempt?->status === 'in_progress' => 'Continue your existing attempt. Its server deadline has not changed.',
            $existingAttempt !== null => 'Your attempt is complete. You can view its submission receipt.',
            ! $closesAt instanceof DateTimeImmutable => 'A valid availability window is required before you can start.',
            $opensAt instanceof DateTimeImmutable && $now < $opensAt => 'This quiz has not opened yet.',
            $now >= $closesAt => 'This quiz is closed for new attempts.',
            default => 'Review the details above, then select Start quiz when you are ready.',
        };

        return Inertia::render('Student/Quizzes/Preflight', [
            'preflight' => $payload,
        ]);
    }
}
