<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class AttemptFinalizationReceipt
{
    public function __construct(
        public string $attemptId,
        public string $status,
        public string $reason,
        public DateTimeImmutable $logicalSubmissionAt,
        public DateTimeImmutable $recordedAt,
        public bool $alreadyFinalized,
    ) {}
}
