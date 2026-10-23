<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Data\AttemptFinalizationReceipt;
use App\Models\QuizAttempt;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Dormant Phase 5E transaction seam. No route, scheduler, or container binding
 * activates this coordinator while Start/save/expiry prerequisites remain open.
 */
final class AttemptFinalizationCoordinator
{
    public function __construct(private readonly AuthoritativeClock $clock) {}

    public function submitByStudent(QuizAttempt $attempt): AttemptFinalizationReceipt
    {
        return $this->transition($attempt, false);
    }

    public function finalizeAtQuizClose(QuizAttempt $attempt): AttemptFinalizationReceipt
    {
        return $this->transition($attempt, true);
    }

    private function transition(QuizAttempt $attempt, bool $closingOperation): AttemptFinalizationReceipt
    {
        return DB::transaction(function () use ($attempt, $closingOperation): AttemptFinalizationReceipt {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($lockedAttempt->status !== 'in_progress') {
                return $this->receipt($lockedAttempt, true);
            }

            $now = $this->clock->now();
            $closesAt = $lockedAttempt->quiz_closes_at;

            if ($closesAt === null) {
                throw new DomainException('The attempt has no persisted quiz closing timestamp.');
            }

            if ($closingOperation && $now < $closesAt) {
                throw new DomainException('The quiz closing timestamp has not been reached.');
            }

            $closed = $now >= $closesAt;
            $logicalSubmissionAt = $closed ? $closesAt : $now;

            $lockedAttempt->forceFill([
                'status' => 'submitted',
                'submitted_at' => $logicalSubmissionAt,
                'finalized_at' => $now,
                'finalization_reason' => $closed ? 'quiz_closed' : 'student_submitted',
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
            $attempt->id,
            $attempt->status,
            $attempt->finalization_reason ?? 'existing_terminal_state',
            $logicalSubmissionAt,
            $recordedAt,
            $alreadyFinalized,
        );
    }
}
