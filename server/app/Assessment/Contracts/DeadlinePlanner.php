<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\DeadlineWindow;
use App\Models\Quiz;

interface DeadlinePlanner
{
    public function forQuiz(Quiz $quiz): DeadlineWindow;
}
