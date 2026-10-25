<?php

namespace Tests\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\QuestionType;
use App\Enums\QuizRevisionStatus;
use App\Enums\QuizStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Quiz;
use App\Models\QuizRevision;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Str;

trait BuildsAssessmentFixtures
{
    protected function assessmentFixture(
        ?User $owner = null,
        ?User $student = null,
        int $questionCount = 2,
        int $wholeSeconds = 600,
        ?int $questionSeconds = 60,
        bool $randomize = true,
        ?DateTimeImmutable $opensAt = null,
        ?DateTimeImmutable $closesAt = null,
    ): array {
        $owner ??= User::factory()->create([
            'role' => AccountRole::Instructor,
            'account_status' => AccountStatus::Active,
        ]);
        $student ??= User::factory()->create([
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Active,
        ]);
        $quiz = new Quiz([
            'title' => 'Synthetic V7 Assessment',
            'course_code' => 'SCI 701',
            'course_name' => 'Assessment Integrity',
            'instructions' => 'Use only server-acknowledged answers.',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = Str::upper(Str::random(8));
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        $revision = new QuizRevision([
            'revision_number' => 1,
            'status' => QuizRevisionStatus::Draft,
            'title' => $quiz->title,
            'course_code' => $quiz->course_code,
            'course_name' => $quiz->course_name,
            'instructions' => $quiz->instructions,
            'display_timezone' => 'Asia/Manila',
            'opens_at' => $opensAt ?? new DateTimeImmutable('2026-10-24T01:00:00+00:00'),
            'closes_at' => $closesAt ?? new DateTimeImmutable('2026-10-24T02:00:00+00:00'),
            'whole_quiz_duration_seconds' => $wholeSeconds,
            'per_question_duration_seconds' => $questionSeconds,
            'randomize_questions' => $randomize,
            'randomize_choices' => $randomize,
        ]);
        $revision->quiz()->associate($quiz);
        $revision->createdBy()->associate($owner);
        $revision->save();

        foreach (range(1, $questionCount) as $position) {
            $question = $revision->questions()->create([
                'type' => $position % 2 === 0 ? QuestionType::TrueFalse : QuestionType::MultipleChoice,
                'prompt' => "Synthetic protected prompt {$position}",
                'position' => $position,
            ]);
            $choices = $position % 2 === 0
                ? [['True', true], ['False', false]]
                : [['Correct option', true], ['Incorrect option', false], ['Another distractor', false]];
            foreach ($choices as $choicePosition => [$text, $correct]) {
                $question->choices()->create([
                    'choice_text' => "{$text} {$position}",
                    'position' => $choicePosition + 1,
                    'is_correct' => $correct,
                ]);
            }
        }

        $revision->status = QuizRevisionStatus::Published;
        $revision->published_at = new DateTimeImmutable('2026-10-23T00:00:00+00:00');
        $revision->publishedBy()->associate($owner);
        $revision->save();
        $quiz->forceFill([
            'status' => QuizStatus::Published,
            'current_published_revision_id' => $revision->id,
        ])->save();

        $record = new SchoolStudentRecord([
            'student_number' => 'SYN-'.Str::upper(Str::random(10)),
            'institutional_email' => $student->email,
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_at' => new DateTimeImmutable('2026-10-23T00:00:00+00:00'),
        ]);
        $record->user()->associate($student);
        $record->verifiedBy()->associate($owner);
        $record->save();

        $assignment = new QuizStudentAssignment(['is_active' => true]);
        $assignment->quiz()->associate($quiz);
        $assignment->studentRecord()->associate($record);
        $assignment->assignedBy()->associate($owner);
        $assignment->save();

        return [$owner, $student, $quiz->fresh(), $revision->fresh()];
    }
}
