<?php

namespace App\Assessment\Support;

use App\Models\Quiz;
use App\Models\QuizAccessEvent;
use App\Models\User;

final class QuizAccessEventRecorder
{
    public function record(User $user, ?Quiz $quiz, string $outcome, string $source): void
    {
        QuizAccessEvent::query()->create([
            'user_id' => $user->getKey(),
            'quiz_id' => $quiz?->getKey(),
            'outcome' => $outcome,
            'source' => $source,
            'occurred_at' => now(),
        ]);
    }
}
