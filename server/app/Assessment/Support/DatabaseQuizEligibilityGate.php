<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Contracts\SchoolEligibilitySource;
use App\Assessment\Data\QuizEligibilityDecision;
use App\Models\Quiz;
use App\Models\User;
use Throwable;

final class DatabaseQuizEligibilityGate implements QuizEligibilityGate
{
    public function __construct(private readonly SchoolEligibilitySource $source) {}

    public function evaluate(User $student, Quiz $quiz): QuizEligibilityDecision
    {
        try {
            return $this->source->evaluate($student, $quiz);
        } catch (Throwable) {
            return QuizEligibilityDecision::sourceUnavailable();
        }
    }
}
