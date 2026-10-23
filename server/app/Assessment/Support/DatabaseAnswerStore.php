<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AnswerStore;
use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Data\AnswerSaveAcknowledgement;
use App\Assessment\Data\AnswerSaveCommand;
use App\Models\AttemptAnswer;
use App\Models\AttemptAnswerReceipt;
use App\Models\AttemptQuestionSnapshot;
use App\Models\AttemptQuestionState;
use App\Models\QuizAttempt;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DatabaseAnswerStore implements AnswerStore
{
    public function __construct(
        private readonly AuthoritativeClock $clock,
        private readonly AttemptFinalizer $finalizer,
        private readonly QuestionDeadlineAdvancer $questionAdvancer,
    ) {}

    public function save(AnswerSaveCommand $command): AnswerSaveAcknowledgement
    {
        $this->validateCommand($command);
        $existingReceipt = AttemptAnswerReceipt::query()
            ->where('attempt_id', $command->attemptId)
            ->where('idempotency_key', $command->idempotencyKey)
            ->first();

        if ($existingReceipt !== null) {
            return $this->replay($existingReceipt, $command);
        }

        $result = DB::transaction(function () use ($command): AnswerSaveAcknowledgement|string {
            $attempt = QuizAttempt::query()->lockForUpdate()->findOrFail($command->attemptId);
            $receipt = AttemptAnswerReceipt::query()
                ->where('attempt_id', $command->attemptId)
                ->where('idempotency_key', $command->idempotencyKey)
                ->first();

            if ($receipt !== null) {
                return $this->replay($receipt, $command);
            }

            if ($attempt->status !== 'in_progress') {
                return 'The attempt is already finalized.';
            }

            $effectiveDeadline = $attempt->effective_deadline_at;
            $now = $this->clock->now();

            if (! $effectiveDeadline instanceof DateTimeImmutable) {
                return 'The attempt is missing its authoritative deadline.';
            }

            if ($now >= $effectiveDeadline) {
                $this->finalizer->finalizeAtEffectiveDeadline($attempt);

                return 'The attempt deadline has been reached.';
            }

            $this->questionAdvancer->synchronize($attempt);
            $attempt->refresh();

            if ($attempt->lock_version !== $command->expectedVersion) {
                return 'The attempt state changed. Refresh the authoritative state before saving again.';
            }

            $snapshot = AttemptQuestionSnapshot::query()
                ->whereKey($command->questionSnapshotId)
                ->where('attempt_id', $attempt->id)
                ->first();

            if ($snapshot === null) {
                return 'The question does not belong to this attempt.';
            }

            $state = AttemptQuestionState::query()
                ->where('attempt_id', $attempt->id)
                ->where('question_snapshot_id', $snapshot->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($state->status !== 'active' || $attempt->current_question_position !== $snapshot->position) {
                return 'The question is not open for answer changes.';
            }

            if ($state->deadline_at instanceof DateTimeImmutable && $now >= $state->deadline_at) {
                return 'The question deadline has been reached.';
            }

            if ($command->selectedChoiceId !== null) {
                $validChoice = collect($snapshot->choices)
                    ->contains(fn (array $choice): bool => ($choice['id'] ?? null) === $command->selectedChoiceId);

                if (! $validChoice) {
                    return 'The selected choice does not belong to this question snapshot.';
                }
            }

            $nextVersion = $attempt->lock_version + 1;
            $answer = AttemptAnswer::query()
                ->where('attempt_id', $attempt->id)
                ->where('question_snapshot_id', $snapshot->id)
                ->first() ?? new AttemptAnswer;
            $answer->attempt()->associate($attempt);
            $answer->questionSnapshot()->associate($snapshot);
            $answer->selected_choice_id = $command->selectedChoiceId;
            $answer->version = $nextVersion;
            $answer->saved_at = $now;
            $answer->save();

            $receipt = new AttemptAnswerReceipt([
                'idempotency_key' => $command->idempotencyKey,
                'requested_choice_id' => $command->selectedChoiceId,
                'answer_version' => $nextVersion,
                'saved_at' => $now,
            ]);
            $receipt->attempt()->associate($attempt);
            $receipt->questionSnapshot()->associate($snapshot);
            $receipt->save();

            $attempt->forceFill([
                'last_saved_at' => $now,
                'lock_version' => $nextVersion,
            ])->save();

            return new AnswerSaveAcknowledgement(
                $attempt->id,
                $snapshot->id,
                $command->idempotencyKey,
                $now,
                $nextVersion,
                false,
            );
        });

        if (is_string($result)) {
            throw new DomainException($result);
        }

        return $result;
    }

    private function replay(
        AttemptAnswerReceipt $receipt,
        AnswerSaveCommand $command,
    ): AnswerSaveAcknowledgement {
        if (
            $receipt->question_snapshot_id !== $command->questionSnapshotId
            || $receipt->requested_choice_id !== $command->selectedChoiceId
        ) {
            throw new DomainException('An idempotency key cannot be reused for a different answer command.');
        }

        return new AnswerSaveAcknowledgement(
            $receipt->attempt_id,
            $receipt->question_snapshot_id,
            $receipt->idempotency_key,
            $receipt->saved_at,
            $receipt->answer_version,
            true,
        );
    }

    private function validateCommand(AnswerSaveCommand $command): void
    {
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{7,63}\z/', $command->idempotencyKey)) {
            throw new DomainException('The answer command requires a valid idempotency key.');
        }

        if ($command->expectedVersion < 0) {
            throw new DomainException('The expected attempt version cannot be negative.');
        }
    }
}
