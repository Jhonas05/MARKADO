<?php

namespace App\Reporting;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

final class QuizResultReportBuilder
{
    public function build(Quiz $quiz, string $sort = 'score'): array
    {
        $sort = in_array($sort, ['score', 'student'], true) ? $sort : 'score';
        $attempts = $quiz->attempts()
            ->with(['student.schoolStudentRecord', 'result'])
            ->get()
            ->map(fn (QuizAttempt $attempt): array => [
                'id' => $attempt->id,
                'studentName' => $attempt->student->name,
                'studentNumber' => $attempt->student->schoolStudentRecord?->student_number,
                'status' => $attempt->status,
                'submittedAt' => $attempt->submitted_at?->timezone('Asia/Manila')->toIso8601String(),
                'scoringStatus' => $attempt->result?->status ?? 'not_started',
                'earnedPoints' => $attempt->result?->earned_points,
                'maximumPoints' => $attempt->result?->maximum_points,
                'releasedAt' => $attempt->result?->released_at?->timezone('Asia/Manila')->toIso8601String(),
                'canRelease' => $attempt->status === 'submitted'
                    && $attempt->result?->status === 'scored'
                    && $attempt->result?->released_at === null,
            ]);

        $attempts = $this->sort($attempts, $sort)->values();
        $scored = $attempts->where('scoringStatus', 'scored');

        return [
            'sort' => $sort,
            'attempts' => $attempts,
            'summary' => [
                'attemptCount' => $attempts->count(),
                'submittedCount' => $attempts->where('status', 'submitted')->count(),
                'scoredCount' => $scored->count(),
                'releasedCount' => $attempts->filter(fn (array $attempt): bool => $attempt['releasedAt'] !== null)->count(),
                'averagePercent' => $scored->isEmpty() ? null : round($scored->average(
                    fn (array $attempt): float => $attempt['maximumPoints'] > 0
                        ? ($attempt['earnedPoints'] / $attempt['maximumPoints']) * 100
                        : 0,
                ), 1),
            ],
        ];
    }

    private function sort(Collection $attempts, string $sort): Collection
    {
        return $attempts->sort(function (array $left, array $right) use ($sort): int {
            if ($sort === 'score') {
                $leftScored = $left['scoringStatus'] === 'scored';
                $rightScored = $right['scoringStatus'] === 'scored';
                if ($leftScored !== $rightScored) {
                    return $leftScored ? -1 : 1;
                }
                if ($leftScored) {
                    $leftRatio = $left['maximumPoints'] > 0 ? $left['earnedPoints'] / $left['maximumPoints'] : 0;
                    $rightRatio = $right['maximumPoints'] > 0 ? $right['earnedPoints'] / $right['maximumPoints'] : 0;
                    if ($leftRatio !== $rightRatio) {
                        return $rightRatio <=> $leftRatio;
                    }
                }
            }

            $name = strnatcasecmp($left['studentName'], $right['studentName']);

            return $name !== 0
                ? $name
                : strnatcasecmp((string) $left['studentNumber'], (string) $right['studentNumber']);
        });
    }
}
