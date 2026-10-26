<?php

namespace App\Gamification\Contracts;

use App\Models\AttemptCoinReward;
use App\Models\QuizAttempt;

interface AttemptRewarder
{
    public function reward(QuizAttempt $attempt): ?AttemptCoinReward;
}
