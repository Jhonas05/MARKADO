<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\ResultReleaseManager;
use App\Assessment\Data\ResultReleaseReceipt;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Models\AttemptResult;
use App\Models\QuizAttempt;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DatabaseResultReleaseManager implements ResultReleaseManager
{
    public function __construct(
        private readonly AuthoritativeClock $clock,
        private readonly AssessmentAuditRecorder $audit,
    ) {}

    public function release(QuizAttempt $attempt, User $professor): ResultReleaseReceipt
    {
        return DB::transaction(function () use ($attempt, $professor): ResultReleaseReceipt {
            $lockedAttempt = QuizAttempt::query()
                ->with('quiz')
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if (
                $professor->role !== AccountRole::Instructor
                || $professor->account_status !== AccountStatus::Active
                || $lockedAttempt->quiz->owner_id !== $professor->id
            ) {
                throw new DomainException('The authenticated Professor cannot release this result.');
            }

            if ($lockedAttempt->status !== 'submitted') {
                throw new DomainException('Only a finalized attempt result can be released.');
            }

            $result = AttemptResult::query()
                ->where('attempt_id', $lockedAttempt->id)
                ->lockForUpdate()
                ->first();

            if ($result === null || $result->status !== 'scored') {
                throw new DomainException('A valid score must be committed before release.');
            }

            if ($result->released_at !== null) {
                return new ResultReleaseReceipt(
                    $lockedAttempt->id,
                    $result->released_by,
                    $result->released_at,
                );
            }

            $releasedAt = $this->clock->now();
            $result->forceFill([
                'released_at' => $releasedAt,
                'released_by' => $professor->id,
            ])->save();
            $lockedAttempt->forceFill([
                'result_released_at' => $releasedAt,
                'result_released_by' => $professor->id,
            ])->save();

            $this->audit->record($lockedAttempt, 'result_released', $professor, [
                'status' => 'released',
                'scoring_version' => $result->scoring_version,
            ]);

            return new ResultReleaseReceipt($lockedAttempt->id, $professor->id, $releasedAt);
        });
    }
}
