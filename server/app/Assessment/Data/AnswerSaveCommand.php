<?php

namespace App\Assessment\Data;

final readonly class AnswerSaveCommand
{
    public function __construct(
        public string $attemptId,
        public string $questionSnapshotId,
        public ?string $selectedChoiceId,
        public string $idempotencyKey,
    ) {}
}
