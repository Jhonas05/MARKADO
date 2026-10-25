<?php

namespace App\Identity\Data;

final readonly class InstitutionalEmailVerificationResult
{
    public const PUBLIC_ACKNOWLEDGEMENT = 'If the account is eligible, verification instructions will be sent.';

    private function __construct(
        public bool $successful,
        public string $reason,
    ) {}

    public static function issued(): self
    {
        return new self(true, 'institutional_verification_issued');
    }

    public static function verified(): self
    {
        return new self(true, 'institutional_mailbox_verified');
    }

    public static function denied(string $reason): self
    {
        return new self(false, $reason);
    }

    public function publicAcknowledgement(): string
    {
        return self::PUBLIC_ACKNOWLEDGEMENT;
    }
}
