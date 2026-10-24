<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\ScoreCalculator;
use App\Assessment\Data\ScoreSummary;
use App\Enums\QuestionType;
use App\Models\AttemptResult;
use App\Models\QuizAttempt;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DatabaseScoreCalculator implements ScoreCalculator
{
    public function __construct(
        private readonly AuthoritativeClock $clock,
        private readonly AssessmentAuditRecorder $audit,
    ) {}

    public function calculate(QuizAttempt $attempt): ScoreSummary
    {
        $result = DB::transaction(function () use ($attempt): ScoreSummary|string {
            $lockedAttempt = QuizAttempt::query()
                ->with(['questionSnapshots', 'answers'])
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($lockedAttempt->status !== 'submitted') {
                return 'Only a terminal attempt can be scored.';
            }

            $persisted = AttemptResult::query()
                ->where('attempt_id', $lockedAttempt->id)
                ->lockForUpdate()
                ->first();

            if ($persisted?->status === 'scored') {
                return new ScoreSummary(
                    $persisted->earned_points,
                    $persisted->maximum_points,
                    $persisted->scoring_version,
                );
            }

            $persisted ??= tap(new AttemptResult(['status' => 'pending']), function (AttemptResult $result) use ($lockedAttempt): void {
                $result->attempt()->associate($lockedAttempt);
                $result->save();
            });

            $answers = $lockedAttempt->answers->keyBy('question_snapshot_id');
            $earned = 0;

            foreach ($lockedAttempt->questionSnapshots as $snapshot) {
                if (! in_array($snapshot->type, [QuestionType::MultipleChoice, QuestionType::TrueFalse], true)) {
                    $persisted->forceFill(['status' => 'failed', 'failure_code' => 'unsupported_question_type'])->save();

                    return 'The attempt contains an unsupported question type.';
                }

                $choiceIds = collect($snapshot->choices)->pluck('id');
                if ($snapshot->correct_choice_id === null || ! $choiceIds->contains($snapshot->correct_choice_id)) {
                    $persisted->forceFill(['status' => 'failed', 'failure_code' => 'invalid_snapshot_key'])->save();

                    return 'The immutable attempt answer key is invalid.';
                }

                $selected = $answers->get($snapshot->id)?->selected_choice_id;
                if ($selected !== null && hash_equals($snapshot->correct_choice_id, $selected)) {
                    $earned++;
                }
            }

            $persisted->forceFill([
                'status' => 'scored',
                'scoring_version' => config('assessment.scoring_version'),
                'earned_points' => $earned,
                'maximum_points' => $lockedAttempt->questionSnapshots->count(),
                'scored_at' => $this->clock->now(),
                'failure_code' => null,
            ])->save();

            $this->audit->record($lockedAttempt, 'attempt_scored', context: [
                'status' => 'scored',
                'scoring_version' => $persisted->scoring_version,
            ]);

            return new ScoreSummary(
                $persisted->earned_points,
                $persisted->maximum_points,
                $persisted->scoring_version,
            );
        });

        if (is_string($result)) {
            throw new DomainException($result);
        }

        return $result;
    }
}
