<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class AttemptDeadlinePlan
{
    public function __construct(
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $quizClosesAt,
        public ?DateTimeImmutable $sessionDeadlineAt,
        public DateTimeImmutable $effectiveDeadlineAt,
    ) {}
}
