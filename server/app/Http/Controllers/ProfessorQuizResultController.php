<?php

namespace App\Http\Controllers;

use App\Assessment\Contracts\ResultReleaseManager;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfessorQuizResultController extends Controller
{
    public function index(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $attempts = $quiz->attempts()
            ->with(['student.schoolStudentRecord', 'result'])
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn (QuizAttempt $attempt): array => [
                'id' => $attempt->id,
                'studentName' => $attempt->student->name,
                'studentNumber' => $attempt->student->schoolStudentRecord?->student_number,
                'status' => $attempt->status,
                'submittedAt' => $attempt->submitted_at?->timezone('Asia/Manila')->toIso8601String(),
                'scoringStatus' => $attempt->result?->status ?? 'not_started',
                'earnedPoints' => $attempt->result?->earned_points,
                'maximumPoints' => $attempt->result?->maximum_points,
                'releasedAt' => $attempt->result?->released_at?->timezone('Asia/Manila')->toIso8601String(),
                'canRelease' => $attempt->status === 'submitted'
                    && $attempt->result?->status === 'scored'
                    && $attempt->result?->released_at === null,
            ]);

        return Inertia::render('Professor/Quizzes/Results', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'courseCode' => $quiz->course_code,
            ],
            'attempts' => $attempts,
        ]);
    }

    public function release(
        Request $request,
        Quiz $quiz,
        QuizAttempt $attempt,
        ResultReleaseManager $releaseManager,
    ): RedirectResponse {
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        $this->authorize('release', $attempt);

        try {
            $releaseManager->release($attempt, $request->user());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['release' => $exception->getMessage()]);
        }

        return back()->with('success', 'The score and total are now visible to this student.');
    }
}
