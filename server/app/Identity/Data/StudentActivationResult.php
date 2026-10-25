<?php

namespace App\Identity\Data;

final readonly class StudentActivationResult
{
    private function __construct(
        public bool $successful,
        public bool $changed,
        public string $reason,
    ) {}

    public static function activated(): self
    {
        return new self(true, true, 'student_activated');
    }

    public static function alreadyActive(): self
    {
        return new self(true, false, 'student_already_active');
    }

    public static function denied(string $reason): self
    {
        return new self(false, false, $reason);
    }
}
