<?php

namespace App\Assessment\Data;

final readonly class AttemptSession
{
    public function __construct(
        public string $attemptId,
        public bool $resumed,
    ) {}
}
