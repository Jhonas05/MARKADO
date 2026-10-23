<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Data\QuizPreflightResult;
use App\Models\Quiz;
use App\Models\User;

final class QuizPreflightResolver
{
    private const GENERIC_UNAVAILABLE = 'This quiz is not available. Check the code or ask your instructor for an updated link.';

    public function __construct(
        private readonly QuizEligibilityGate $eligibilityGate,
        private readonly QuizAccessEventRecorder $events,
    ) {}

    public function resolve(User $student, string $code, string $source = 'join_link'): QuizPreflightResult
    {
        $normalizedCode = strtoupper(trim($code));

        if (! preg_match('/\A[A-HJ-NP-Z2-9]{8}\z/', $normalizedCode)) {
            $this->events->record($student, null, 'unavailable', $source);

            return new QuizPreflightResult('unavailable', self::GENERIC_UNAVAILABLE);
        }

        $quiz = Quiz::query()
            ->with(['currentPublishedRevision' => fn ($query) => $query->withCount('questions')])
            ->where('access_code', $normalizedCode)
            ->first();

        if ($quiz === null || $student->cannot('viewPreflight', $quiz)) {
            $this->events->record($student, $quiz, 'unavailable', $source);

            return new QuizPreflightResult('unavailable', self::GENERIC_UNAVAILABLE);
        }

        $eligibility = $this->eligibilityGate->evaluate($student, $quiz);

        if (! $eligibility->allowed) {
            $this->events->record($student, $quiz, 'unavailable', $source);

            return new QuizPreflightResult(
                'unavailable',
                self::GENERIC_UNAVAILABLE,
            );
        }

        $revision = $quiz->currentPublishedRevision;
        $this->events->record($student, $quiz, 'information_available', $source);

        return new QuizPreflightResult('information_available', 'Review the quiz information and instructions.', [
            'id' => $quiz->id,
            'title' => $revision->title,
            'courseCode' => $revision->course_code,
            'courseName' => $revision->course_name,
            'instructions' => $revision->instructions,
            'questionCount' => $revision->questions_count,
            'displayTimezone' => $revision->display_timezone,
        ]);
    }
}
