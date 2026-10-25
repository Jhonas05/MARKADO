<?php

namespace App\Identity\Contracts;

use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

interface InstitutionalMailboxProof
{
    public function evaluate(User $student, string $claimedEmail): StudentIdentityDecision;
}
