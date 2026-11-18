<?php

namespace App\Identity\Support;

use App\Identity\Contracts\InstitutionalVerificationAbuseGate;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

final class RegistrationInstitutionalVerificationAbuseGate implements InstitutionalVerificationAbuseGate
{
    public function evaluate(User $student, ?string $networkContext): StudentIdentityDecision
    {
        return RegistrationEnvironment::enabled()
            ? StudentIdentityDecision::matched()
            : StudentIdentityDecision::denied('verification_abuse_policy_unavailable');
    }
}
