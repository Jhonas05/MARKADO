<?php

namespace App\Assessment\Data;

final readonly class QuizEligibilityDecision
{
    private function __construct(
        public bool $allowed,
        public string $reason,
    ) {}

    public static function pendingPolicy(): self
    {
        return new self(false, 'eligibility_policy_pending');
    }

    public static function allowed(): self
    {
        return new self(true, 'eligible');
    }
}
