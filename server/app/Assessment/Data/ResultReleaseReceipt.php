<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class ResultReleaseReceipt
{
    public function __construct(
        public string $attemptId,
        public int $releasedBy,
        public DateTimeImmutable $releasedAt,
    ) {}
}
