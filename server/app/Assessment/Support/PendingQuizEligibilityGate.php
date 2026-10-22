<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Data\QuizEligibilityDecision;
use App\Models\Quiz;
use App\Models\User;

final class PendingQuizEligibilityGate implements QuizEligibilityGate
{
    public function evaluate(User $student, Quiz $quiz): QuizEligibilityDecision
    {
        return QuizEligibilityDecision::pendingPolicy();
    }
}
