<?php

namespace App\Services;

use App\Identity\Contracts\StudentIdentitySource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class QuizStudentAssignmentService
{
    public function __construct(private readonly StudentIdentitySource $identitySource) {}

    /**
     * @return array{assignment: QuizStudentAssignment, outcome: 'assigned'|'already_assigned'|'reactivated'}
     */
    public function assign(User $actor, Quiz $quiz, string $studentNumber): array
    {
        $this->authorize($actor, $quiz);

        return DB::transaction(function () use ($actor, $quiz, $studentNumber): array {
            $record = SchoolStudentRecord::query()
                ->with('user')
                ->where('student_number', $studentNumber)
                ->lockForUpdate()
                ->first();

            $student = $record?->user;
            if ($record === null || $student === null || ! $this->identitySource->evaluate($student)->matched) {
                throw ValidationException::withMessages([
                    'student_number' => 'Student unavailable. No eligible active Student matched that institutional student number.',
                ]);
            }

            $now = now();
            $inserted = DB::table('quiz_student_assignments')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'quiz_id' => $quiz->getKey(),
                'student_record_id' => $record->getKey(),
                'is_active' => true,
                'assigned_by' => $actor->getKey(),
                'deactivated_by' => null,
                'deactivated_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $assignment = QuizStudentAssignment::query()
                ->whereBelongsTo($quiz)
                ->whereBelongsTo($record, 'studentRecord')
                ->lockForUpdate()
                ->firstOrFail();

            if ($inserted === 1) {
                return ['assignment' => $assignment, 'outcome' => 'assigned'];
            }

            if ($assignment->is_active) {
                return ['assignment' => $assignment, 'outcome' => 'already_assigned'];
            }

            $assignment->forceFill([
                'is_active' => true,
                'assigned_by' => $actor->getKey(),
                'deactivated_by' => null,
                'deactivated_at' => null,
            ])->save();

            return ['assignment' => $assignment->fresh(), 'outcome' => 'reactivated'];
        }, 3);
    }

    /**
     * @return 'removed'|'already_removed'
     */
    public function remove(User $actor, Quiz $quiz, string $assignmentId): string
    {
        $this->authorize($actor, $quiz);

        return DB::transaction(function () use ($actor, $quiz, $assignmentId): string {
            $assignment = QuizStudentAssignment::query()
                ->whereBelongsTo($quiz)
                ->whereKey($assignmentId)
                ->with('studentRecord')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $assignment->is_active) {
                return 'already_removed';
            }

            $studentId = $assignment->studentRecord?->user_id;
            $hasAttempt = $studentId !== null && QuizAttempt::query()
                ->whereBelongsTo($quiz)
                ->where('student_id', $studentId)
                ->exists();

            if ($hasAttempt) {
                throw ValidationException::withMessages([
                    'assignment' => 'This direct assignment cannot be removed because the Student already has an attempt for this quiz.',
                ]);
            }

            $assignment->forceFill([
                'is_active' => false,
                'deactivated_by' => $actor->getKey(),
                'deactivated_at' => now(),
            ])->save();

            return 'removed';
        }, 3);
    }

    private function authorize(User $actor, Quiz $quiz): void
    {
        if (! $actor->can('manageStudentAssignments', $quiz)) {
            throw new AuthorizationException;
        }
    }
}
