<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\TimerPlan;
use App\Models\Quiz;

interface TimerPlanner
{
    public function forQuiz(Quiz $quiz): TimerPlan;
}
