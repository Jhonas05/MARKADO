<?php

namespace App\Identity\Support;

use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\SchoolStudentRecord;
use App\Models\User;

final class RegistrationStudentActivationSecurityGate implements StudentActivationSecurityGate
{
    public function __construct(private readonly TrustedRosterRecordPolicy $roster) {}

    public function evaluate(User $student, string $rosterRecordId): StudentIdentityDecision
    {
        if (! RegistrationEnvironment::enabled()) {
            return StudentIdentityDecision::denied('activation_security_policy_unavailable');
        }

        $record = SchoolStudentRecord::query()->find($rosterRecordId);

        return $record !== null && $this->roster->accepts($record)
            ? StudentIdentityDecision::matched()
            : StudentIdentityDecision::denied('trusted_roster_evidence_stale_or_unavailable');
    }
}
