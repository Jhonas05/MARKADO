<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\FinalizationReceipt;
use App\Models\QuizAttempt;

interface AttemptFinalizer
{
    public function submit(QuizAttempt $attempt): FinalizationReceipt;

    public function expire(QuizAttempt $attempt): FinalizationReceipt;
}
