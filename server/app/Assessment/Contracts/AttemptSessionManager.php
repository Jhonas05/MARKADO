<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\AttemptSession;
use App\Models\Quiz;
use App\Models\User;

interface AttemptSessionManager
{
    public function resumeOrCreate(User $student, Quiz $quiz): AttemptSession;
}
