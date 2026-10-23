<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\QuizEligibilityDecision;
use App\Models\Quiz;
use App\Models\User;

interface SchoolEligibilitySource
{
    public function evaluate(User $student, Quiz $quiz): QuizEligibilityDecision;
}
