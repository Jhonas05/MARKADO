<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Data\AttemptSession;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\QuizRevisionStatus;
use App\Enums\QuizStatus;
use App\Models\AttemptQuestionSnapshot;
use App\Models\AttemptQuestionState;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizRevision;
use App\Models\User;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DatabaseAttemptSessionManager implements AttemptSessionManager
{
    public function __construct(
        private readonly QuizEligibilityGate $eligibility,
        private readonly AuthoritativeClock $clock,
        private readonly AttemptFinalizer $finalizer,
        private readonly QuestionDeadlineAdvancer $questionAdvancer,
    ) {}

    public function resumeOrCreate(User $student, Quiz $quiz): AttemptSession
    {
        if ($student->role !== AccountRole::Student || $student->account_status !== AccountStatus::Active) {
            throw new DomainException('Only an active student account can open an assessment attempt.');
        }

        return DB::transaction(function () use ($student, $quiz): AttemptSession {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $existing = QuizAttempt::query()
                ->where('quiz_id', $lockedQuiz->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $this->resume($existing);
            }

            if ($lockedQuiz->status !== QuizStatus::Published || $lockedQuiz->current_published_revision_id === null) {
                throw new DomainException('The quiz is not published.');
            }

            $decision = $this->eligibility->evaluate($student, $lockedQuiz);

            if (! $decision->allowed) {
                throw new DomainException('The student is not eligible to start this quiz.');
            }

            $revision = QuizRevision::query()
                ->with('questions.choices')
                ->whereKey($lockedQuiz->current_published_revision_id)
                ->where('quiz_id', $lockedQuiz->id)
                ->where('status', QuizRevisionStatus::Published->value)
                ->firstOrFail();
            $now = $this->clock->now();

            $this->assertStartWindow($revision, $now);
            $this->assertTimerConfiguration($revision);

            $deadlinePlan = (new HardCloseDeadlineGuard($this->clock))->planAttempt(
                $now,
                $revision->whole_quiz_duration_seconds,
                $revision->closes_at,
            );
            $seed = bin2hex(random_bytes(32));
            $attempt = new QuizAttempt([
                'status' => 'in_progress',
                'started_at' => $deadlinePlan->startedAt,
                'quiz_closes_at' => $deadlinePlan->quizClosesAt,
                'session_deadline_at' => $deadlinePlan->sessionDeadlineAt,
                'effective_deadline_at' => $deadlinePlan->effectiveDeadlineAt,
                'permutation_seed' => $seed,
                'current_question_position' => 1,
                'progress_state' => 'active',
                'lock_version' => 0,
            ]);
            $attempt->quiz()->associate($lockedQuiz);
            $attempt->student()->associate($student);
            $attempt->revision()->associate($revision);
            $attempt->save();

            $this->createSnapshots($attempt, $revision, $seed, $now);

            return $this->toSession($attempt, false, $now);
        });
    }

    private function resume(QuizAttempt $attempt): AttemptSession
    {
        $now = $this->clock->now();

        if ($attempt->status === 'in_progress') {
            $this->questionAdvancer->synchronize($attempt);
            $attempt->refresh();
        }

        return $this->toSession($attempt, true, $now);
    }

    private function createSnapshots(
        QuizAttempt $attempt,
        QuizRevision $revision,
        string $seed,
        DateTimeImmutable $startedAt,
    ): void {
        $questions = $revision->questions;

        if ($revision->randomize_questions) {
            $questions = $questions->sortBy(fn ($question) => hash_hmac('sha256', $question->id, $seed))->values();
        }

        foreach ($questions->values() as $questionIndex => $question) {
            $choices = $question->choices;

            if ($revision->randomize_choices) {
                $choices = $choices->sortBy(fn ($choice) => hash_hmac('sha256', $choice->id, $seed))->values();
            }

            $snapshotChoices = $choices->values()->map(fn ($choice) => [
                'id' => $choice->id,
                'text' => $choice->choice_text,
            ])->all();
            $correctChoiceId = $choices->firstWhere('is_correct', true)?->id;
            $snapshot = new AttemptQuestionSnapshot([
                'source_question_id' => $question->source_question_id,
                'source_revision_question_id' => $question->id,
                'position' => $questionIndex + 1,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'choices' => $snapshotChoices,
                'correct_choice_id' => $correctChoiceId,
            ]);
            $snapshot->attempt()->associate($attempt);
            $snapshot->save();

            $state = new AttemptQuestionState(['status' => $questionIndex === 0 ? 'active' : 'pending']);
            $state->attempt()->associate($attempt);
            $state->questionSnapshot()->associate($snapshot);

            if ($questionIndex === 0) {
                $state->started_at = $startedAt;
                $state->deadline_at = $this->questionDeadline($revision, $attempt, $startedAt);
            }

            $state->save();
        }
    }

    private function questionDeadline(
        QuizRevision $revision,
        QuizAttempt $attempt,
        DateTimeImmutable $startedAt,
    ): ?DateTimeImmutable {
        if ($revision->per_question_duration_seconds === null) {
            return null;
        }

        $questionDeadline = $startedAt->modify("+{$revision->per_question_duration_seconds} seconds");

        return $questionDeadline < $attempt->effective_deadline_at
            ? $questionDeadline
            : $attempt->effective_deadline_at;
    }

    private function toSession(QuizAttempt $attempt, bool $resumed, DateTimeImmutable $now): AttemptSession
    {
        $effectiveDeadline = $attempt->effective_deadline_at;

        if (! $effectiveDeadline instanceof DateTimeImmutable) {
            throw new DomainException('The attempt is missing its effective deadline.');
        }

        $questionDeadline = null;

        if ($attempt->current_question_position !== null) {
            $questionDeadline = AttemptQuestionState::query()
                ->where('attempt_id', $attempt->id)
                ->whereHas('questionSnapshot', fn ($query) => $query
                    ->where('position', $attempt->current_question_position))
                ->value('deadline_at');
            $questionDeadline = $questionDeadline === null
                ? null
                : new DateTimeImmutable((string) $questionDeadline);
        }

        return new AttemptSession(
            $attempt->id,
            $resumed,
            $attempt->status,
            $attempt->lock_version,
            $attempt->current_question_position,
            $now,
            $effectiveDeadline,
            $questionDeadline,
        );
    }

    private function assertStartWindow(QuizRevision $revision, DateTimeImmutable $now): void
    {
        if (! $revision->closes_at instanceof DateTimeImmutable) {
            throw new DomainException('A published quiz must have a closing timestamp before attempts can start.');
        }

        if ($revision->opens_at instanceof DateTimeImmutable && $now < $revision->opens_at) {
            throw new DomainException('The quiz is not open yet.');
        }

        if ($now >= $revision->closes_at) {
            throw new DomainException('The quiz is already closed.');
        }
    }

    private function assertTimerConfiguration(QuizRevision $revision): void
    {
        foreach ([$revision->whole_quiz_duration_seconds, $revision->per_question_duration_seconds] as $seconds) {
            if ($seconds !== null && $seconds < 1) {
                throw new DomainException('Configured assessment durations must be positive.');
            }
        }
    }
}
