<?php

namespace Tests\Feature;

use App\Assessment\Contracts\SchoolEligibilitySource;
use App\Assessment\Data\QuizEligibilityDecision;
use App\Enums\AccountRole;
use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Quiz;
use App\Models\QuizChoice;
use App\Models\QuizQuestion;
use App\Models\QuizSectionAssignment;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolSection;
use App\Models\SchoolSectionMembership;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use App\Services\QuizPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SchoolEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_directly_assigned_student_receives_only_safe_preflight_information(): void
    {
        [$owner, $quiz] = $this->publishedQuiz('DRECT222');
        $student = $this->student();
        $record = $this->verifiedRecord($student, $owner);
        $this->assignStudent($quiz, $record, $owner);

        $this->actingAs($student)
            ->get('/student/quizzes/join/DRECT222')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('preflight.state', 'information_available')
                ->where('preflight.quiz.title', 'Cell Biology Checkpoint')
                ->where('preflight.quiz.questionCount', 1)
                ->where('preflight.canStart', false)
                ->missing('preflight.quiz.questions')
                ->missing('preflight.quiz.choices')
                ->missing('preflight.quiz.answerKey')
                ->missing('preflight.quiz.isCorrect')
                ->missing('preflight.quiz.accessCode'));

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_verified_student_with_active_membership_in_assigned_section_is_eligible(): void
    {
        [$owner, $quiz] = $this->publishedQuiz('SECTN222');
        $student = $this->student();
        $record = $this->verifiedRecord($student, $owner);
        $section = $this->section('BIO-1A', $owner);
        $this->membership($section, $record, $owner, true);
        $this->assignSection($quiz, $section, $owner);

        $this->actingAs($student)
            ->get('/student/quizzes/join/SECTN222')
            ->assertInertia(fn ($page) => $page
                ->where('preflight.state', 'information_available')
                ->where('preflight.canStart', false));
    }

    public function test_unverified_wrong_section_inactive_membership_and_missing_assignment_are_denied_identically(): void
    {
        [$owner, $quiz] = $this->publishedQuiz('DENED222');
        $assignedSection = $this->section('BIO-1A', $owner);
        $this->assignSection($quiz, $assignedSection, $owner);

        $unverified = $this->student();
        $this->studentRecord($unverified, StudentVerificationStatus::Unverified);

        $wrongSection = $this->student();
        $wrongRecord = $this->verifiedRecord($wrongSection, $owner);
        $this->membership($this->section('BIO-1B', $owner), $wrongRecord, $owner, true);

        $inactiveMember = $this->student();
        $inactiveRecord = $this->verifiedRecord($inactiveMember, $owner);
        $this->membership($assignedSection, $inactiveRecord, $owner, false);

        $unassigned = $this->student();
        $this->verifiedRecord($unassigned, $owner);

        $incompleteVerification = $this->student();
        $incompleteRecord = $this->studentRecord($incompleteVerification, StudentVerificationStatus::Verified);
        $this->assignStudent($quiz, $incompleteRecord, $owner);

        foreach ([$unverified, $wrongSection, $inactiveMember, $unassigned, $incompleteVerification] as $student) {
            $this->actingAs($student)
                ->get('/student/quizzes/join/DENED222')
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('preflight.state', 'unavailable')
                    ->where('preflight.message', 'This quiz is not available. Check the code or ask your instructor for an updated link.')
                    ->where('preflight.quiz', null)
                    ->where('preflight.canStart', false)
                    ->missing('preflight.questions')
                    ->missing('preflight.answerKey'));
        }

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_unavailable_trusted_source_denies_by_default_without_leaking_content(): void
    {
        $this->app->bind(SchoolEligibilitySource::class, fn () => new class implements SchoolEligibilitySource
        {
            public function evaluate(User $student, Quiz $quiz): QuizEligibilityDecision
            {
                throw new RuntimeException('Synthetic roster outage');
            }
        });

        [, $quiz] = $this->publishedQuiz('UTAGE222');
        $student = $this->student();

        $this->actingAs($student)
            ->get('/student/quizzes/join/UTAGE222')
            ->assertInertia(fn ($page) => $page
                ->where('preflight.state', 'unavailable')
                ->where('preflight.quiz', null)
                ->missing('preflight.questions')
                ->missing('preflight.answerKey'));

        $this->assertDatabaseHas('quiz_access_events', [
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'outcome' => 'unavailable',
        ]);
    }

    public function test_valid_code_and_direct_resource_guessing_do_not_bypass_authorization(): void
    {
        [$owner, $quiz] = $this->publishedQuiz('GUESS222');
        $student = $this->student();
        $otherInstructor = User::factory()->create(['role' => AccountRole::Instructor]);

        $this->actingAs($student)
            ->get('/student/quizzes/join/GUESS222')
            ->assertInertia(fn ($page) => $page
                ->where('preflight.state', 'unavailable')
                ->where('preflight.quiz', null));
        $this->actingAs($student)->get("/professor/quizzes/{$quiz->id}/edit")->assertForbidden();
        $this->actingAs($otherInstructor)->get("/professor/quizzes/{$quiz->id}/edit")->assertForbidden();
        $this->actingAs($otherInstructor)->post("/professor/quizzes/{$quiz->id}/unpublish")->assertForbidden();

        $this->assertSame($owner->id, $quiz->owner_id);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_inactive_student_record_and_inactive_assignment_are_denied(): void
    {
        [$owner, $quiz] = $this->publishedQuiz('NACT2222');
        $student = $this->student();
        $record = $this->verifiedRecord($student, $owner);
        $assignment = $this->assignStudent($quiz, $record, $owner);
        $record->update(['is_active' => false]);

        $this->actingAs($student)
            ->get('/student/quizzes/join/NACT2222')
            ->assertInertia(fn ($page) => $page->where('preflight.state', 'unavailable'));

        $record->update(['is_active' => true]);
        $assignment->update(['is_active' => false]);

        $this->actingAs($student)
            ->get('/student/quizzes/join/NACT2222')
            ->assertInertia(fn ($page) => $page->where('preflight.state', 'unavailable'));
    }

    public function test_assignment_records_include_actor_and_timestamp_audit_metadata_without_mutation_routes(): void
    {
        [$owner, $quiz] = $this->publishedQuiz('AUDT2222');
        $record = $this->verifiedRecord($this->student(), $owner);
        $assignment = $this->assignStudent($quiz, $record, $owner);

        $this->assertSame($owner->id, $assignment->assigned_by);
        $this->assertNotNull($assignment->created_at);
        $this->assertNotNull($record->verified_at);
        $this->assertSame($owner->id, $record->verified_by);
        $this->post("/professor/quizzes/{$quiz->id}/assignments")->assertStatus(405);
    }

    private function publishedQuiz(string $code): array
    {
        $owner = User::factory()->create(['role' => AccountRole::Instructor]);
        $quiz = new Quiz([
            'title' => 'Cell Biology Checkpoint',
            'course_code' => 'BIO 104',
            'course_name' => 'General Biology',
            'instructions' => 'Read every item carefully.',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = $code;
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        $question = new QuizQuestion([
            'type' => QuestionType::MultipleChoice,
            'prompt' => 'PROTECTED QUESTION PROMPT',
            'position' => 1,
        ]);
        $question->quiz()->associate($quiz);
        $question->save();
        foreach ([['PROTECTED WRONG CHOICE', false], ['PROTECTED ANSWER KEY', true]] as $index => [$text, $correct]) {
            $choice = new QuizChoice([
                'choice_text' => $text,
                'position' => $index + 1,
                'is_correct' => $correct,
            ]);
            $choice->question()->associate($question);
            $choice->save();
        }
        app(QuizPublicationService::class)->publish($owner, $quiz);

        return [$owner, $quiz->fresh()];
    }

    private function student(): User
    {
        return User::factory()->create(['role' => AccountRole::Student]);
    }

    private function verifiedRecord(User $student, User $verifier): SchoolStudentRecord
    {
        $record = $this->studentRecord($student, StudentVerificationStatus::Verified);
        $record->verifiedBy()->associate($verifier);
        $record->verified_at = now();
        $record->save();

        return $record;
    }

    private function studentRecord(User $student, StudentVerificationStatus $status): SchoolStudentRecord
    {
        $record = new SchoolStudentRecord([
            'student_number' => 'SYN-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'institutional_email' => $student->email,
            'verification_status' => $status,
            'is_active' => true,
        ]);
        $record->user()->associate($student);
        $record->save();

        return $record;
    }

    private function section(string $code, User $creator): SchoolSection
    {
        $section = new SchoolSection(['code' => $code, 'name' => "Synthetic {$code}", 'is_active' => true]);
        $section->createdBy()->associate($creator);
        $section->save();

        return $section;
    }

    private function membership(
        SchoolSection $section,
        SchoolStudentRecord $record,
        User $actor,
        bool $active,
    ): SchoolSectionMembership {
        $membership = new SchoolSectionMembership(['is_active' => $active]);
        $membership->section()->associate($section);
        $membership->studentRecord()->associate($record);
        $membership->recordedBy()->associate($actor);
        $membership->save();

        return $membership;
    }

    private function assignStudent(Quiz $quiz, SchoolStudentRecord $record, User $actor): QuizStudentAssignment
    {
        $assignment = new QuizStudentAssignment(['is_active' => true]);
        $assignment->quiz()->associate($quiz);
        $assignment->studentRecord()->associate($record);
        $assignment->assignedBy()->associate($actor);
        $assignment->save();

        return $assignment;
    }

    private function assignSection(Quiz $quiz, SchoolSection $section, User $actor): QuizSectionAssignment
    {
        $assignment = new QuizSectionAssignment(['is_active' => true]);
        $assignment->quiz()->associate($quiz);
        $assignment->section()->associate($section);
        $assignment->assignedBy()->associate($actor);
        $assignment->save();

        return $assignment;
    }
}
