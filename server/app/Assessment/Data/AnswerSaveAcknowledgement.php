<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class AnswerSaveAcknowledgement
{
    public function __construct(
        public string $attemptId,
        public string $questionSnapshotId,
        public string $idempotencyKey,
        public DateTimeImmutable $savedAt,
        public int $version,
        public bool $replayed,
    ) {}
}
