<?php

namespace App\Identity\Contracts;

use App\Identity\Data\StudentIdentityDecision;
use App\Models\User;

interface StudentIdentitySource
{
    public function evaluate(User $student): StudentIdentityDecision;

    public function matchClaim(
        User $student,
        string $studentNumber,
        string $institutionalEmail,
    ): StudentIdentityDecision;
}
