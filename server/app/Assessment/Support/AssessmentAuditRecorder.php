<?php

namespace App\Assessment\Support;

use App\Models\AssessmentAuditEvent;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

final class AssessmentAuditRecorder
{
    private const ALLOWED_CONTEXT_KEYS = [
        'assignment_id',
        'outcome',
        'reason',
        'receipt_id',
        'scoring_version',
        'status',
    ];

    public function record(
        QuizAttempt $attempt,
        string $eventType,
        ?User $actor = null,
        array $context = [],
    ): AssessmentAuditEvent {
        return $this->recordForQuiz(
            $attempt->quiz,
            $eventType,
            $actor,
            $context,
            $attempt,
        );
    }

    public function recordQuizEvent(
        Quiz $quiz,
        string $eventType,
        User $actor,
        array $context = [],
    ): AssessmentAuditEvent {
        return $this->recordForQuiz($quiz, $eventType, $actor, $context);
    }

    private function recordForQuiz(
        Quiz $quiz,
        string $eventType,
        ?User $actor,
        array $context,
        ?QuizAttempt $attempt = null,
    ): AssessmentAuditEvent {
        $event = new AssessmentAuditEvent([
            'event_type' => $eventType,
            'occurred_at' => now(),
            'context' => collect($context)->only(self::ALLOWED_CONTEXT_KEYS)->all() ?: null,
        ]);
        $event->quiz()->associate($quiz);
        if ($attempt !== null) {
            $event->attempt()->associate($attempt);
        }
        if ($actor !== null) {
            $event->actor()->associate($actor);
        }
        $event->save();

        return $event;
    }
}
