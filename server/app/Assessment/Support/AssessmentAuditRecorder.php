<?php

namespace App\Assessment\Support;

use App\Models\AssessmentAuditEvent;
use App\Models\QuizAttempt;
use App\Models\User;

final class AssessmentAuditRecorder
{
    private const ALLOWED_CONTEXT_KEYS = [
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
        $event = new AssessmentAuditEvent([
            'event_type' => $eventType,
            'occurred_at' => now(),
            'context' => collect($context)->only(self::ALLOWED_CONTEXT_KEYS)->all() ?: null,
        ]);
        $event->quiz()->associate($attempt->quiz_id);
        $event->attempt()->associate($attempt);
        if ($actor !== null) {
            $event->actor()->associate($actor);
        }
        $event->save();

        return $event;
    }
}
