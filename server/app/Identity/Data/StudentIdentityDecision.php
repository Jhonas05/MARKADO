<?php

namespace App\Identity\Data;

final readonly class StudentIdentityDecision
{
    private function __construct(
        public bool $matched,
        public string $reason,
    ) {}

    public static function matched(): self
    {
        return new self(true, 'matched');
    }

    public static function denied(string $reason): self
    {
        return new self(false, $reason);
    }
}
