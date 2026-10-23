<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\SchoolEligibilitySource;
use App\Assessment\Data\QuizEligibilityDecision;
use App\Enums\StudentVerificationStatus;
use App\Models\Quiz;
use App\Models\QuizSectionAssignment;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;

final class DatabaseSchoolEligibilitySource implements SchoolEligibilitySource
{
    public function evaluate(User $student, Quiz $quiz): QuizEligibilityDecision
    {
        $record = SchoolStudentRecord::query()
            ->whereBelongsTo($student)
            ->where('verification_status', StudentVerificationStatus::Verified->value)
            ->whereNotNull('verified_at')
            ->where('is_active', true)
            ->first();

        if ($record === null) {
            return QuizEligibilityDecision::denied('school_identity_not_verified');
        }

        $directlyAssigned = QuizStudentAssignment::query()
            ->whereBelongsTo($quiz)
            ->whereBelongsTo($record, 'studentRecord')
            ->where('is_active', true)
            ->exists();

        if ($directlyAssigned) {
            return QuizEligibilityDecision::allowed();
        }

        $sectionAssigned = QuizSectionAssignment::query()
            ->whereBelongsTo($quiz)
            ->where('is_active', true)
            ->whereHas('section', fn ($section) => $section
                ->where('is_active', true)
                ->whereHas('memberships', fn ($membership) => $membership
                    ->where('student_record_id', $record->id)
                    ->where('is_active', true)))
            ->exists();

        return $sectionAssigned
            ? QuizEligibilityDecision::allowed()
            : QuizEligibilityDecision::denied('not_assigned_to_quiz');
    }
}
