<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\ResultReleaseReceipt;
use App\Models\QuizAttempt;
use App\Models\User;

interface ResultReleaseManager
{
    public function release(QuizAttempt $attempt, User $professor): ResultReleaseReceipt;
}
