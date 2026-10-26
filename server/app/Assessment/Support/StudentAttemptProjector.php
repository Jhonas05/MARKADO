<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Gamification\Support\GamificationEnvironment;
use App\Models\QuizAttempt;

final class StudentAttemptProjector
{
    public function __construct(private readonly AuthoritativeClock $clock) {}

    public function project(QuizAttempt $attempt): array
    {
        $attempt->load([
            'quiz',
            'revision',
            'questionSnapshots.state',
            'answers',
            'result',
            'coinReward',
        ]);

        $answers = $attempt->answers->keyBy('question_snapshot_id');
        $current = $attempt->current_question_position === null
            ? null
            : $attempt->questionSnapshots->firstWhere('position', $attempt->current_question_position);
        $currentState = $current?->state;
        $currentAnswer = $current === null ? null : $answers->get($current->id);
        $result = $attempt->result;

        $resultProjection = ['state' => 'not_available'];
        if ($attempt->status === 'submitted') {
            if ($result?->status === 'failed') {
                $resultProjection = ['state' => 'processing_failed'];
            } elseif ($result === null || $result->status === 'pending') {
                $resultProjection = ['state' => 'processing'];
            } elseif ($result->released_at === null) {
                $resultProjection = ['state' => 'withheld'];
            } else {
                $resultProjection = [
                    'state' => 'released',
                    'earnedPoints' => $result->earned_points,
                    'maximumPoints' => $result->maximum_points,
                    'releasedAt' => $result->released_at->toIso8601String(),
                ];
            }
        }

        return [
            'id' => $attempt->id,
            'status' => $attempt->status,
            'progressState' => $attempt->progress_state,
            'version' => $attempt->lock_version,
            'serverNow' => $this->clock->now()->format(DATE_ATOM),
            'displayTimezone' => $attempt->revision->display_timezone,
            'quiz' => [
                'id' => $attempt->quiz_id,
                'title' => $attempt->revision->title,
                'courseCode' => $attempt->revision->course_code,
                'courseName' => $attempt->revision->course_name,
                'instructions' => $attempt->revision->instructions,
            ],
            'timing' => [
                'effectiveDeadlineAt' => $attempt->effective_deadline_at?->toIso8601String(),
                'questionDeadlineAt' => $currentState?->deadline_at?->toIso8601String(),
                'perQuestionEnabled' => $attempt->revision->per_question_duration_seconds !== null,
                'wholeQuizEnabled' => $attempt->revision->whole_quiz_duration_seconds !== null,
            ],
            'navigation' => $attempt->questionSnapshots->map(fn ($snapshot): array => [
                'position' => $snapshot->position,
                'status' => $snapshot->state?->status,
                'answered' => $answers->get($snapshot->id)?->selected_choice_id !== null,
                'current' => $snapshot->position === $attempt->current_question_position,
            ])->values(),
            'currentQuestion' => $current === null ? null : [
                'id' => $current->id,
                'position' => $current->position,
                'total' => $attempt->questionSnapshots->count(),
                'type' => $current->type->value,
                'prompt' => $current->prompt,
                'choices' => collect($current->choices)->map(fn (array $choice): array => [
                    'id' => $choice['id'],
                    'text' => $choice['text'],
                ])->values(),
                'selectedChoiceId' => $currentAnswer?->selected_choice_id,
                'savedAt' => $currentAnswer?->saved_at?->toIso8601String(),
            ],
            'receipt' => $attempt->status === 'submitted' ? [
                'id' => $attempt->terminal_receipt_id,
                'submittedAt' => $attempt->submitted_at?->toIso8601String(),
                'reason' => $attempt->finalization_reason,
            ] : null,
            'result' => $resultProjection,
            'reward' => GamificationEnvironment::enabled() && $attempt->coinReward !== null ? [
                'coins' => $attempt->coinReward->coins,
                'awardedAt' => $attempt->coinReward->awarded_at->toIso8601String(),
            ] : null,
        ];
    }
}
