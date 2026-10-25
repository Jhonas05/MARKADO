<?php

namespace App\Identity\Contracts;

use App\Identity\Data\RegistrationClaimDecision;
use App\Models\User;

interface RegistrationClaimVerifier
{
    public function verify(
        User $student,
        string $studentNumber,
        string $institutionalEmail,
    ): RegistrationClaimDecision;
}
