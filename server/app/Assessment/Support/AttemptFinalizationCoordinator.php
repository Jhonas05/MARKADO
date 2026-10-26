<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\ScoreCalculator;
use App\Assessment\Data\AttemptFinalizationReceipt;
use App\Gamification\Contracts\AttemptRewarder;
use App\Models\AttemptQuestionState;
use App\Models\AttemptResult;
use App\Models\QuizAttempt;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * One authoritative terminal-state coordinator for requests and workers.
 */
final class AttemptFinalizationCoordinator implements AttemptFinalizer
{
    public function __construct(
        private readonly AuthoritativeClock $clock,
        private readonly ?ScoreCalculator $scoreCalculator = null,
        private readonly ?AssessmentAuditRecorder $audit = null,
        private readonly ?AttemptRewarder $attemptRewarder = null,
    ) {}

    public function submitByStudent(QuizAttempt $attempt): AttemptFinalizationReceipt
    {
        return $this->transition($attempt, allowStudentSubmission: true);
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

        return $this->transition($attempt, requireAutomaticDeadline: true);
    }

    public function finalizeAtEffectiveDeadline(QuizAttempt $attempt): AttemptFinalizationReceipt
    {
        return $this->transition($attempt, requireAutomaticDeadline: true);
    }

    public function finalizeAtQuestionDeadline(
        QuizAttempt $attempt,
        DateTimeImmutable $questionDeadlineAt,
    ): AttemptFinalizationReceipt {
        if ($this->clock->now() < $questionDeadlineAt) {
            throw new DomainException('The final-question deadline has not been reached.');
        }

        return $this->transition($attempt, requireAutomaticDeadline: true);
    }

    private function transition(
        QuizAttempt $attempt,
        bool $requireAutomaticDeadline = false,
        bool $allowStudentSubmission = false,
    ): AttemptFinalizationReceipt {
        $newTransition = false;

        $receipt = DB::transaction(function () use (
            $attempt,
            $requireAutomaticDeadline,
            $allowStudentSubmission,
            &$newTransition,
        ): AttemptFinalizationReceipt {
            $lockedAttempt = QuizAttempt::query()
                ->with('revision')
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($lockedAttempt->status !== 'in_progress') {
                if ($lockedAttempt->terminal_receipt_id === null) {
                    $lockedAttempt->terminal_receipt_id = (string) Str::uuid();
                    $lockedAttempt->save();
                }
                $this->ensurePendingResult($lockedAttempt);
                $this->attemptRewarder?->reward($lockedAttempt);

                return $this->receipt($lockedAttempt, true);
            }

            $now = $this->clock->now();
            $closesAt = $lockedAttempt->quiz_closes_at;
            $effectiveDeadlineAt = $lockedAttempt->effective_deadline_at;

            if (! $closesAt instanceof DateTimeImmutable || ! $effectiveDeadlineAt instanceof DateTimeImmutable) {
                throw new DomainException('The attempt is missing persisted authoritative deadlines.');
            }

            $candidates = [];
            if ($now >= $effectiveDeadlineAt) {
                $candidates[] = [
                    'at' => $effectiveDeadlineAt,
                    'reason' => $effectiveDeadlineAt == $closesAt ? 'quiz_closed' : 'session_expired',
                    'priority' => $effectiveDeadlineAt == $closesAt ? 30 : 20,
                ];
            }

            $finalQuestionDeadline = $this->finalQuestionDeadline($lockedAttempt);
            if ($finalQuestionDeadline !== null && $now >= $finalQuestionDeadline) {
                $candidates[] = [
                    'at' => $finalQuestionDeadline,
                    'reason' => 'final_question_expired',
                    'priority' => 10,
                ];
            }

            $automaticCandidateCount = count($candidates);
            if ($allowStudentSubmission) {
                $candidates[] = ['at' => $now, 'reason' => 'student_submitted', 'priority' => 0];
            }

            if ($requireAutomaticDeadline && $automaticCandidateCount === 0) {
                throw new DomainException('No authoritative terminal deadline has been reached.');
            }

            if ($candidates === []) {
                throw new DomainException('The attempt cannot be finalized by this operation.');
            }

            usort($candidates, function (array $left, array $right): int {
                $timeOrder = $left['at']->format('U.u') <=> $right['at']->format('U.u');

                return $timeOrder !== 0 ? $timeOrder : $right['priority'] <=> $left['priority'];
            });
            $selected = $candidates[0];

            AttemptQuestionState::query()
                ->where('attempt_id', $lockedAttempt->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'locked',
                    'locked_at' => $selected['at'],
                    'lock_reason' => $selected['reason'],
                    'updated_at' => now(),
                ]);

            $lockedAttempt->forceFill([
                'status' => 'submitted',
                'submitted_at' => $selected['at'],
                'finalized_at' => $now,
                'finalization_reason' => $selected['reason'],
                'terminal_receipt_id' => (string) Str::uuid(),
                'current_question_position' => null,
                'progress_state' => 'finalized',
                'lock_version' => $lockedAttempt->lock_version + 1,
            ])->save();
            $this->ensurePendingResult($lockedAttempt);
            $this->attemptRewarder?->reward($lockedAttempt);
            $newTransition = true;

            return $this->receipt($lockedAttempt, false);
        });

        $terminalAttempt = QuizAttempt::query()->findOrFail($receipt->attemptId);
        if ($newTransition) {
            $this->audit?->record($terminalAttempt, 'attempt_finalized', context: [
                'reason' => $receipt->reason,
                'receipt_id' => $receipt->receiptId,
                'status' => $receipt->status,
            ]);
        }

        $this->scoreWithoutReopening($terminalAttempt);

        return $receipt;
    }

    private function finalQuestionDeadline(QuizAttempt $attempt): ?DateTimeImmutable
    {
        if ($attempt->revision?->per_question_duration_seconds === null) {
            return null;
        }

        $lastSnapshotId = $attempt->questionSnapshots()->reorder()->orderByDesc('position')->value('id');
        if ($lastSnapshotId === null) {
            return null;
        }

        $state = AttemptQuestionState::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_snapshot_id', $lastSnapshotId)
            ->first();

        return $state?->deadline_at instanceof DateTimeImmutable ? $state->deadline_at : null;
    }

    private function ensurePendingResult(QuizAttempt $attempt): void
    {
        AttemptResult::query()->firstOrCreate(
            ['attempt_id' => $attempt->id],
            ['status' => 'pending'],
        );
    }

    private function scoreWithoutReopening(QuizAttempt $attempt): void
    {
        if ($this->scoreCalculator === null) {
            return;
        }

        try {
            $this->scoreCalculator->calculate($attempt);
        } catch (Throwable) {
            AttemptResult::query()
                ->where('attempt_id', $attempt->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'failure_code' => 'scoring_exception',
                    'updated_at' => now(),
                ]);
            $this->audit?->record($attempt, 'attempt_scoring_failed', context: [
                'status' => 'failed',
                'scoring_version' => config('assessment.scoring_version'),
            ]);
        }
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
