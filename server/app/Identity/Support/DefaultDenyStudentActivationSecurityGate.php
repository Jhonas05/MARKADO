<?php

namespace App\Identity\Support;

use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

final class DefaultDenyStudentActivationSecurityGate implements StudentActivationSecurityGate
{
    public function evaluate(User $student, string $rosterRecordId): StudentIdentityDecision
    {
        return StudentIdentityDecision::denied('activation_security_policy_unavailable');
    }
}
