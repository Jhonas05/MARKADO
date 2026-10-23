<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Models\AttemptQuestionState;
use App\Models\QuizAttempt;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class QuestionDeadlineAdvancer
{
    public function __construct(private readonly AuthoritativeClock $clock) {}

    public function synchronize(QuizAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt): void {
            $lockedAttempt = QuizAttempt::query()
                ->with('revision')
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($lockedAttempt->status !== 'in_progress' || $lockedAttempt->current_question_position === null) {
                return;
            }

            $questionSeconds = $lockedAttempt->revision?->per_question_duration_seconds;

            if ($questionSeconds === null) {
                return;
            }

            if ($questionSeconds < 1) {
                throw new DomainException('The persisted per-question duration is invalid.');
            }

            $effectiveDeadline = $lockedAttempt->effective_deadline_at;

            if (! $effectiveDeadline instanceof DateTimeImmutable) {
                throw new DomainException('The attempt is missing its effective deadline.');
            }

            $now = $this->clock->now();

            if ($now >= $effectiveDeadline) {
                return;
            }

            while ($lockedAttempt->current_question_position !== null) {
                $snapshot = $lockedAttempt->questionSnapshots()
                    ->where('position', $lockedAttempt->current_question_position)
                    ->firstOrFail();
                $state = AttemptQuestionState::query()
                    ->where('attempt_id', $lockedAttempt->id)
                    ->where('question_snapshot_id', $snapshot->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $deadline = $state->deadline_at;

                if (! $deadline instanceof DateTimeImmutable || $now < $deadline) {
                    return;
                }

                $state->forceFill([
                    'status' => 'locked',
                    'locked_at' => $deadline,
                    'lock_reason' => 'question_expired',
                ])->save();

                $nextSnapshot = $lockedAttempt->questionSnapshots()
                    ->where('position', '>', $snapshot->position)
                    ->orderBy('position')
                    ->first();

                if ($nextSnapshot === null) {
                    $lockedAttempt->forceFill([
                        'current_question_position' => null,
                        'progress_state' => 'awaiting_final_question_policy',
                        'lock_version' => $lockedAttempt->lock_version + 1,
                    ])->save();

                    return;
                }

                $logicalStartedAt = $deadline;
                $uncappedDeadline = $logicalStartedAt->modify("+{$questionSeconds} seconds");
                $nextDeadline = $uncappedDeadline < $effectiveDeadline
                    ? $uncappedDeadline
                    : $effectiveDeadline;
                $nextState = AttemptQuestionState::query()
                    ->where('attempt_id', $lockedAttempt->id)
                    ->where('question_snapshot_id', $nextSnapshot->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $nextState->forceFill([
                    'status' => 'active',
                    'started_at' => $logicalStartedAt,
                    'deadline_at' => $nextDeadline,
                ])->save();
                $lockedAttempt->forceFill([
                    'current_question_position' => $nextSnapshot->position,
                    'progress_state' => 'active',
                    'lock_version' => $lockedAttempt->lock_version + 1,
                ])->save();

                if ($now < $nextDeadline) {
                    return;
                }
            }
        });
    }
}
