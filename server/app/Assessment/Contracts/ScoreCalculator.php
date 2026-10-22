<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\ScoreSummary;
use App\Models\QuizAttempt;

interface ScoreCalculator
{
    public function calculate(QuizAttempt $attempt): ScoreSummary;
}
