<?php

namespace App\Identity\Support;

use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Contracts\InstitutionalMailboxProof;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

final class DatabaseInstitutionalMailboxProof implements InstitutionalMailboxProof
{
    public function __construct(
        private readonly InstitutionalEmailPolicy $emailPolicy,
    ) {}

    public function evaluate(User $student, string $claimedEmail): StudentIdentityDecision
    {
        $accountEmail = is_string($student->email)
            ? $this->emailPolicy->normalizeAllowedAddress($student->email)
            : null;
        $normalizedClaim = $this->emailPolicy->normalizeAllowedAddress($claimedEmail);

        if ($accountEmail === null || $normalizedClaim === null || ! hash_equals($accountEmail, $normalizedClaim)) {
            return StudentIdentityDecision::denied('institutional_identity_mismatch');
        }

        if ($student->email_verified_at === null) {
            return StudentIdentityDecision::denied('institutional_mailbox_unverified');
        }

        return StudentIdentityDecision::matched();
    }
}
