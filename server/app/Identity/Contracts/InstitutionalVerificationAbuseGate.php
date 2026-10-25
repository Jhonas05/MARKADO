<?php

namespace App\Identity\Contracts;

use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

interface InstitutionalVerificationAbuseGate
{
    public function evaluate(User $student, ?string $networkContext): StudentIdentityDecision;
}
