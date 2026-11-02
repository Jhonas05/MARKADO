<?php

namespace App\Identity\Support;

use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

final class LocalStudentActivationSecurityGate implements StudentActivationSecurityGate
{
    public function evaluate(User $student, string $rosterRecordId): StudentIdentityDecision
    {
        return LocalRegistrationEnvironment::enabled()
            ? StudentIdentityDecision::matched()
            : StudentIdentityDecision::denied('activation_security_policy_unavailable');
    }
}
