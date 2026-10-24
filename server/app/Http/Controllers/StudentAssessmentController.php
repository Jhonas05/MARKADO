<?php

namespace App\Http\Controllers;

use App\Assessment\Contracts\AnswerStore;
use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Data\AnswerSaveCommand;
use App\Assessment\Support\QuestionDeadlineAdvancer;
use App\Assessment\Support\QuestionProgressionService;
use App\Assessment\Support\StudentAttemptProjector;
use App\Http\Requests\ProgressAttemptRequest;
use App\Http\Requests\SaveAttemptAnswerRequest;
use App\Http\Requests\SubmitAttemptRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentAssessmentController extends Controller
{
    public function store(
        Request $request,
        Quiz $quiz,
        AttemptSessionManager $sessions,
    ): RedirectResponse {
        $this->authorize('viewPreflight', $quiz);

        try {
            $session = $sessions->resumeOrCreate($request->user(), $quiz);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['assessment' => $exception->getMessage()]);
        }

        return to_route('student.attempts.show', $session->attemptId);
    }

    public function show(
        Request $request,
        QuizAttempt $attempt,
        AttemptSessionManager $sessions,
        StudentAttemptProjector $projector,
    ): Response {
        $this->authorize('view', $attempt);
        $sessions->resumeOrCreate($request->user(), $attempt->quiz);

        return Inertia::render('Student/Quizzes/Assessment', [
            'attempt' => $projector->project($attempt->fresh()),
        ]);
    }

    public function save(SaveAttemptAnswerRequest $request, QuizAttempt $attempt, AnswerStore $answers): RedirectResponse
    {
        $data = $request->validated();

        try {
            $acknowledgement = $answers->save(new AnswerSaveCommand(
                $attempt->id,
                $data['question_snapshot_id'],
                $data['selected_choice_id'] ?? null,
                $data['idempotency_key'],
                $data['expected_version'],
            ));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['answer' => $exception->getMessage()]);
        }

        return back()->with('assessment', [
            'state' => 'saved',
            'idempotencyKey' => $acknowledgement->idempotencyKey,
            'version' => $acknowledgement->version,
            'savedAt' => $acknowledgement->savedAt->format(DATE_ATOM),
            'replayed' => $acknowledgement->replayed,
        ]);
    }

    public function advance(
        ProgressAttemptRequest $request,
        QuizAttempt $attempt,
        QuestionProgressionService $progression,
    ): RedirectResponse {
        try {
            $progression->advance($attempt, $request->integer('expected_version'));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['progression' => $exception->getMessage()]);
        }

        return back();
    }

    public function submit(
        SubmitAttemptRequest $request,
        QuizAttempt $attempt,
        AttemptFinalizer $finalizer,
        QuestionDeadlineAdvancer $deadlineAdvancer,
    ): RedirectResponse {
        try {
            DB::transaction(function () use ($request, $attempt, $finalizer, $deadlineAdvancer): void {
                $locked = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
                $deadlineAdvancer->synchronize($locked);
                $locked->refresh();

                if ($locked->status === 'in_progress') {
                    if ($locked->lock_version !== $request->integer('expected_version')) {
                        throw new DomainException('The attempt state changed. Refresh before submitting.');
                    }

                    $answered = $locked->answers()->whereNotNull('selected_choice_id')->count();
                    if ($answered !== $locked->questionSnapshots()->count()) {
                        throw new DomainException('Voluntary submission with unanswered questions is not approved. Complete every item or wait for automatic expiry.');
                    }
                }

                $finalizer->submitByStudent($locked);
            });
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['submission' => $exception->getMessage()]);
        }

        return to_route('student.attempts.show', $attempt);
    }
}
