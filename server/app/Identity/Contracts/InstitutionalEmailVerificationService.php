<?php

namespace App\Identity\Contracts;

use App\Identity\Data\InstitutionalEmailVerificationResult;
use App\Models\User;

interface InstitutionalEmailVerificationService
{
    public function issue(User $student, ?string $networkContext = null): InstitutionalEmailVerificationResult;

    public function consume(User $student, #[\SensitiveParameter] string $token): InstitutionalEmailVerificationResult;
}
