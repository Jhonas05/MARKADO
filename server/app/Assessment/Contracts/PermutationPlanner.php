<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\StablePermutation;
use App\Models\Quiz;
use App\Models\User;

interface PermutationPlanner
{
    public function forStudent(Quiz $quiz, User $student): StablePermutation;
}
