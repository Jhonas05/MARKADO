<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\AttemptFinalizationReceipt;
use App\Models\QuizAttempt;

interface AttemptFinalizer
{
    public function submitByStudent(QuizAttempt $attempt): AttemptFinalizationReceipt;

    public function finalizeAtEffectiveDeadline(QuizAttempt $attempt): AttemptFinalizationReceipt;

    public function finalizeAtQuizClose(QuizAttempt $attempt): AttemptFinalizationReceipt;
}
