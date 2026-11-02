<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\QuizStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfessorQuizAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.institutional_email_domains', ['example.test']);
    }

    public function test_owner_assigns_an_eligible_student_by_exact_number_and_sees_minimal_identity_fields(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $student = $this->student();
        $record = $this->recordFor($student, $owner, studentNumber: '2026-00017');

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/student-assignments", [
                'student_number' => '2026-00017',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Student assigned to this quiz.');

        $assignment = QuizStudentAssignment::query()->sole();
        $this->assertSame($quiz->id, $assignment->quiz_id);
        $this->assertSame($record->id, $assignment->student_record_id);
        $this->assertSame($owner->id, $assignment->assigned_by);
        $this->assertTrue($assignment->is_active);

        $response = $this->actingAs($owner)->get("/professor/quizzes/{$quiz->id}/edit");
        $response->assertOk()
            ->assertDontSee($student->email)
            ->assertInertia(fn (Assert $page) => $page
                ->where('quiz.assignments.managementEnabled', true)
                ->where('quiz.assignments.directStudentCount', 1)
                ->where('quiz.assignments.directStudents.0.assignmentId', $assignment->id)
                ->where('quiz.assignments.directStudents.0.studentNumber', '2026-00017')
                ->where('quiz.assignments.directStudents.0.studentName', $student->name)
                ->where('quiz.assignments.directStudents.0.state', 'assigned')
                ->where('quiz.assignments.directStudents.0.canRemove', true)
                ->missing('quiz.assignments.directStudents.0.institutionalEmail')
                ->missing('quiz.assignments.directStudents.0.userId')
                ->missing('quiz.assignments.directStudents.0.recordId'));
    }

    public function test_repeated_assignment_is_idempotent_and_inactive_assignment_is_reactivated(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $record = $this->recordFor($this->student(), $owner);
        $path = "/professor/quizzes/{$quiz->id}/student-assignments";

        $this->actingAs($owner)->post($path, ['student_number' => $record->student_number]);
        $assignment = QuizStudentAssignment::query()->sole();

        $this->actingAs($owner)
            ->post($path, ['student_number' => $record->student_number])
            ->assertSessionHas('success', 'This Student is already assigned to this quiz.');

        $this->assertSame(1, QuizStudentAssignment::query()->count());
        $this->assertSame($assignment->id, QuizStudentAssignment::query()->sole()->id);

        $assignment->forceFill([
            'is_active' => false,
            'deactivated_by' => $owner->id,
            'deactivated_at' => now(),
        ])->save();

        $this->actingAs($owner)
            ->post($path, ['student_number' => $record->student_number])
            ->assertSessionHas('success', 'Student assignment restored.');

        $reactivated = QuizStudentAssignment::query()->sole();
        $this->assertSame($assignment->id, $reactivated->id);
        $this->assertTrue($reactivated->is_active);
        $this->assertNull($reactivated->deactivated_by);
        $this->assertNull($reactivated->deactivated_at);
    }

    public function test_foreign_instructor_and_non_instructors_cannot_mutate_assignments(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $record = $this->recordFor($this->student(), $owner);

        foreach ([$this->instructor(), $this->student(), User::factory()->create([
            'role' => AccountRole::AcademicAdministrator,
        ])] as $actor) {
            $this->actingAs($actor)
                ->post("/professor/quizzes/{$quiz->id}/student-assignments", [
                    'student_number' => $record->student_number,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('quiz_student_assignments', 0);
    }

    public function test_missing_or_ineligible_students_share_one_privacy_safe_error(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $path = "/professor/quizzes/{$quiz->id}/student-assignments";
        $expected = 'Student unavailable. No eligible active Student matched that institutional student number.';

        $cases = [];

        $unverifiedRoster = $this->student();
        $cases[] = $this->recordFor(
            $unverifiedRoster,
            $owner,
            StudentVerificationStatus::Unverified,
        )->student_number;

        $inactiveRoster = $this->student();
        $cases[] = $this->recordFor(
            $inactiveRoster,
            $owner,
            active: false,
        )->student_number;

        $pendingStudent = $this->student(['account_status' => AccountStatus::Pending]);
        $cases[] = $this->recordFor($pendingStudent, $owner)->student_number;

        $disabledStudent = $this->student(['account_status' => AccountStatus::Disabled]);
        $cases[] = $this->recordFor($disabledStudent, $owner)->student_number;

        $unverifiedMailbox = $this->student(['email_verified_at' => null]);
        $cases[] = $this->recordFor($unverifiedMailbox, $owner)->student_number;

        $mismatchedMailbox = $this->student();
        $cases[] = $this->recordFor(
            $mismatchedMailbox,
            $owner,
            institutionalEmail: 'different@example.test',
        )->student_number;

        $cases[] = 'UNKNOWN-2026-9999';

        foreach ($cases as $studentNumber) {
            $this->actingAs($owner)
                ->post($path, ['student_number' => $studentNumber])
                ->assertSessionHasErrors(['student_number' => $expected]);
        }

        $this->assertDatabaseCount('quiz_student_assignments', 0);
    }

    public function test_owner_removes_an_assignment_and_records_the_actor_when_no_attempt_exists(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $record = $this->recordFor($this->student(), $owner);
        $assignment = $this->assignmentFor($quiz, $record, $owner);

        $this->actingAs($owner)
            ->delete("/professor/quizzes/{$quiz->id}/student-assignments/{$assignment->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Direct Student assignment removed.');

        $assignment->refresh();
        $this->assertFalse($assignment->is_active);
        $this->assertSame($owner->id, $assignment->deactivated_by);
        $this->assertNotNull($assignment->deactivated_at);
    }

    public function test_assignment_cannot_be_removed_after_the_student_has_an_attempt(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $student = $this->student();
        $record = $this->recordFor($student, $owner);
        $assignment = $this->assignmentFor($quiz, $record, $owner);
        $attempt = new QuizAttempt(['status' => 'in_progress', 'started_at' => now()]);
        $attempt->quiz()->associate($quiz);
        $attempt->student()->associate($student);
        $attempt->save();

        $this->actingAs($owner)
            ->delete("/professor/quizzes/{$quiz->id}/student-assignments/{$assignment->id}")
            ->assertSessionHasErrors([
                'assignment' => 'This direct assignment cannot be removed because the Student already has an attempt for this quiz.',
            ]);

        $this->assertTrue($assignment->fresh()->is_active);
        $this->actingAs($owner)
            ->get("/professor/quizzes/{$quiz->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->where('quiz.assignments.directStudents.0.canRemove', false));
    }

    public function test_assignment_id_must_belong_to_the_owned_quiz(): void
    {
        $owner = $this->instructor();
        $firstQuiz = $this->quizFor($owner);
        $secondQuiz = $this->quizFor($owner);
        $record = $this->recordFor($this->student(), $owner);
        $assignment = $this->assignmentFor($firstQuiz, $record, $owner);

        $this->actingAs($owner)
            ->delete("/professor/quizzes/{$secondQuiz->id}/student-assignments/{$assignment->id}")
            ->assertNotFound();

        $this->assertTrue($assignment->fresh()->is_active);
    }

    private function instructor(): User
    {
        return User::factory()->create(['role' => AccountRole::Instructor]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function student(array $attributes = []): User
    {
        return User::factory()->create([
            'role' => AccountRole::Student,
            ...$attributes,
        ]);
    }

    private function quizFor(User $owner): Quiz
    {
        $quiz = new Quiz([
            'title' => 'Synthetic Assignment Quiz',
            'course_code' => 'SYN 930',
            'course_name' => 'Secure Assignment Testing',
            'instructions' => 'Use server-managed access only.',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = Str::upper(Str::random(8));
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        return $quiz;
    }

    private function recordFor(
        User $student,
        User $verifier,
        StudentVerificationStatus $status = StudentVerificationStatus::Verified,
        bool $active = true,
        ?string $studentNumber = null,
        ?string $institutionalEmail = null,
    ): SchoolStudentRecord {
        $record = new SchoolStudentRecord([
            'student_number' => $studentNumber ?? 'SYN-'.Str::upper(Str::random(10)),
            'institutional_email' => $institutionalEmail ?? $student->email,
            'verification_status' => $status,
            'is_active' => $active,
            'verified_at' => $status === StudentVerificationStatus::Verified ? now() : null,
            'linked_at' => now(),
        ]);
        $record->user()->associate($student);
        $record->verifiedBy()->associate($verifier);
        $record->save();

        return $record;
    }

    private function assignmentFor(Quiz $quiz, SchoolStudentRecord $record, User $actor): QuizStudentAssignment
    {
        $assignment = new QuizStudentAssignment(['is_active' => true]);
        $assignment->quiz()->associate($quiz);
        $assignment->studentRecord()->associate($record);
        $assignment->assignedBy()->associate($actor);
        $assignment->save();

        return $assignment;
    }
}
