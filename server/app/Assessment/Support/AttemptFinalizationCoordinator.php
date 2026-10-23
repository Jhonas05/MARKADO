<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Data\AttemptFinalizationReceipt;
use App\Models\QuizAttempt;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Atomic terminal-state coordinator. It remains route-gated, but is exercised
 * by trusted assessment-engine services and database-backed tests.
 */
final class AttemptFinalizationCoordinator implements AttemptFinalizer
{
    public function __construct(private readonly AuthoritativeClock $clock) {}

    public function submitByStudent(QuizAttempt $attempt): AttemptFinalizationReceipt
    {
        return $this->transition($attempt);
    }

    public function finalizeAtQuizClose(QuizAttempt $attempt): AttemptFinalizationReceipt
    {
        $closesAt = $attempt->quiz_closes_at;

        if (! $closesAt instanceof DateTimeImmutable) {
            throw new DomainException('The attempt has no persisted quiz closing timestamp.');
        }

        if ($this->clock->now() < $closesAt) {
            throw new DomainException('The quiz closing timestamp has not been reached.');
        }

        return $this->transition($attempt, requireDeadline: true);
    }

    public function finalizeAtEffectiveDeadline(QuizAttempt $attempt): AttemptFinalizationReceipt
    {
        return $this->transition($attempt, requireDeadline: true);
    }

    private function transition(QuizAttempt $attempt, bool $requireDeadline = false): AttemptFinalizationReceipt
    {
        return DB::transaction(function () use ($attempt, $requireDeadline): AttemptFinalizationReceipt {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($lockedAttempt->status !== 'in_progress') {
                if ($lockedAttempt->terminal_receipt_id === null) {
                    $lockedAttempt->terminal_receipt_id = (string) Str::uuid();
                    $lockedAttempt->save();
                }

                return $this->receipt($lockedAttempt, true);
            }

            $now = $this->clock->now();
            $closesAt = $lockedAttempt->quiz_closes_at;
            $effectiveDeadlineAt = $lockedAttempt->effective_deadline_at;

            if (! $closesAt instanceof DateTimeImmutable || ! $effectiveDeadlineAt instanceof DateTimeImmutable) {
                throw new DomainException('The attempt is missing persisted authoritative deadlines.');
            }

            $deadlineReached = $now >= $effectiveDeadlineAt;

            if ($requireDeadline && ! $deadlineReached) {
                throw new DomainException('The effective attempt deadline has not been reached.');
            }

            $closed = $deadlineReached && $effectiveDeadlineAt >= $closesAt;
            $logicalSubmissionAt = $deadlineReached ? $effectiveDeadlineAt : $now;
            $reason = $deadlineReached
                ? ($closed ? 'quiz_closed' : 'session_expired')
                : 'student_submitted';

            $lockedAttempt->forceFill([
                'status' => 'submitted',
                'submitted_at' => $logicalSubmissionAt,
                'finalized_at' => $now,
                'finalization_reason' => $reason,
                'terminal_receipt_id' => (string) Str::uuid(),
                'progress_state' => 'finalized',
                'lock_version' => $lockedAttempt->lock_version + 1,
            ])->save();

            return $this->receipt($lockedAttempt, false);
        });
    }

    private function receipt(QuizAttempt $attempt, bool $alreadyFinalized): AttemptFinalizationReceipt
    {
        $logicalSubmissionAt = $attempt->submitted_at;
        $recordedAt = $attempt->finalized_at ?? $logicalSubmissionAt;

        if (! $logicalSubmissionAt instanceof DateTimeImmutable || ! $recordedAt instanceof DateTimeImmutable) {
            throw new DomainException('A terminal attempt is missing its finalization timestamps.');
        }

        return new AttemptFinalizationReceipt(
            $attempt->terminal_receipt_id,
            $attempt->id,
            $attempt->status,
            $attempt->finalization_reason ?? 'existing_terminal_state',
            $logicalSubmissionAt,
            $recordedAt,
            $alreadyFinalized,
        );
    }
}
