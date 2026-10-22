<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class FinalizationReceipt
{
    public function __construct(
        public string $attemptId,
        public string $status,
        public DateTimeImmutable $finalizedAt,
    ) {}
}
