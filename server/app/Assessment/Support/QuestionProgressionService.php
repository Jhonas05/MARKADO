<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Models\AttemptAnswer;
use App\Models\AttemptQuestionState;
use App\Models\QuizAttempt;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class QuestionProgressionService
{
    public function __construct(
        private readonly AuthoritativeClock $clock,
        private readonly QuestionDeadlineAdvancer $advancer,
    ) {}

    public function advance(QuizAttempt $attempt, int $expectedVersion): QuizAttempt
    {
        $result = DB::transaction(function () use ($attempt, $expectedVersion): QuizAttempt|string {
            $locked = QuizAttempt::query()->with('revision')->lockForUpdate()->findOrFail($attempt->id);

            $this->advancer->synchronize($locked);
            $locked->refresh();
            if ($locked->status !== 'in_progress') {
                return 'The attempt is already finalized.';
            }

            $now = $this->clock->now();
            if (! $locked->effective_deadline_at instanceof DateTimeImmutable) {
                return 'The attempt is missing its authoritative deadline.';
            }

            if ($locked->lock_version !== $expectedVersion) {
                return 'The attempt state changed. Refresh before continuing.';
            }

            $current = $locked->questionSnapshots()
                ->where('position', $locked->current_question_position)
                ->firstOrFail();
            $answer = AttemptAnswer::query()
                ->where('attempt_id', $locked->id)
                ->where('question_snapshot_id', $current->id)
                ->first();

            if ($answer?->selected_choice_id === null) {
                return 'Save an answer before moving to the next question.';
            }

            $next = $locked->questionSnapshots()
                ->where('position', '>', $current->position)
                ->orderBy('position')
                ->first();
            if ($next === null) {
                return 'This is the final question. Use the separate submission confirmation.';
            }

            AttemptQuestionState::query()
                ->where('attempt_id', $locked->id)
                ->where('question_snapshot_id', $current->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->forceFill([
                    'status' => 'locked',
                    'locked_at' => $now,
                    'lock_reason' => 'student_advanced',
                ])->save();

            $nextDeadline = null;
            if ($locked->revision->per_question_duration_seconds !== null) {
                $uncapped = $now->modify("+{$locked->revision->per_question_duration_seconds} seconds");
                $nextDeadline = $uncapped < $locked->effective_deadline_at
                    ? $uncapped
                    : $locked->effective_deadline_at;
            }

            AttemptQuestionState::query()
                ->where('attempt_id', $locked->id)
                ->where('question_snapshot_id', $next->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->forceFill([
                    'status' => 'active',
                    'started_at' => $now,
                    'deadline_at' => $nextDeadline,
                ])->save();

            $locked->forceFill([
                'current_question_position' => $next->position,
                'progress_state' => 'active',
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            return $locked;
        });

        if (is_string($result)) {
            throw new DomainException($result);
        }

        return $result;
    }
}
