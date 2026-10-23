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

    public static function denied(string $reason): self
    {
        return new self(false, $reason);
    }

    public static function sourceUnavailable(): self
    {
        return new self(false, 'trusted_source_unavailable');
    }

    public static function allowed(): self
    {
        return new self(true, 'eligible');
    }
}
