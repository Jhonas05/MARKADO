<?php

namespace App\Assessment\Data;

use DateTimeImmutable;

final readonly class DeadlineWindow
{
    public function __construct(
        public ?DateTimeImmutable $opensAt,
        public ?DateTimeImmutable $closesAt,
        public string $timezone,
    ) {}
}
