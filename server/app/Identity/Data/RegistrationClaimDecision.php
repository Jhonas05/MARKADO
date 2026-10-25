<?php

namespace App\Identity\Data;

final readonly class RegistrationClaimDecision
{
    private function __construct(
        public bool $verified,
        public string $reason,
        public ?string $rosterRecordId,
    ) {}

    public static function verified(string $rosterRecordId): self
    {
        return new self(true, 'registration_claim_verified', $rosterRecordId);
    }

    public static function denied(string $reason): self
    {
        return new self(false, $reason, null);
    }
}
