<?php

namespace App\Http\Controllers;

use App\Assessment\Support\AssessmentEngineCapabilities;
use App\Assessment\Support\QuizPreflightResolver;
use App\Http\Requests\JoinQuizRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentQuizJoinController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Student/Quizzes/Join', [
            'policyNotice' => 'Quiz information is available only for published quizzes assigned through verified school records. Starting an attempt remains disabled.',
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
    ): Response {
        $preflight = $resolver->resolve($request->user(), $code);

        return Inertia::render('Student/Quizzes/Preflight', [
            'preflight' => $preflight->toArray(),
            'capabilities' => $capabilities->toArray(),
        ]);
    }
}
