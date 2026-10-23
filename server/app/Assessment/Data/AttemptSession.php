<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class AttemptSession
{
    public function __construct(
        public string $attemptId,
        public bool $resumed,
        public string $status,
        public int $version,
        public ?int $currentQuestionPosition,
        public DateTimeImmutable $serverNow,
        public DateTimeImmutable $effectiveDeadlineAt,
        public ?DateTimeImmutable $questionDeadlineAt,
    ) {}
}
