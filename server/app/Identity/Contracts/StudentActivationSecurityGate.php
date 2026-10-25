<?php

namespace App\Identity\Contracts;

use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

interface StudentActivationSecurityGate
{
    public function evaluate(User $student, string $rosterRecordId): StudentIdentityDecision;
}
