<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class AnswerSaveAcknowledgement
{
    public function __construct(
        public string $attemptId,
        public string $questionSnapshotId,
        public DateTimeImmutable $savedAt,
        public string $version,
    ) {}
}
