<?php

namespace App\Identity\Contracts;

use App\Identity\Data\StudentActivationResult;
use App\Models\User;

interface StudentActivationService
{
    public function activate(
        User $student,
        string $studentNumber,
        string $institutionalEmail,
    ): StudentActivationResult;
}
