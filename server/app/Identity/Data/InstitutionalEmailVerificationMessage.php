<?php

namespace App\Identity\Data;

use Carbon\CarbonImmutable;

final readonly class InstitutionalEmailVerificationMessage
{
    public function __construct(
        public int $studentId,
        public string $recipient,
        #[\SensitiveParameter]
        public string $token,
        public CarbonImmutable $expiresAt,
    ) {}
}
