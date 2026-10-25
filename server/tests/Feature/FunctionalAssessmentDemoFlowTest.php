<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\AssessmentAuditEvent;
use App\Models\AttemptResult;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FunctionalAssessmentDemoFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('assessment.routes_enabled', true);
        config()->set('assessment.expiration_enabled', false);
        config()->set('identity.institutional_email_domains', ['students.example.edu']);
    }

    public function test_authenticated_professor_to_student_assessment_flow_uses_real_http_routes(): void
    {
        $professorPassword = 'synthetic-professor-passphrase';
        $studentPassword = 'synthetic-student-passphrase';
        $professor = User::factory()->create([
            'name' => 'Professor Demo',
            'email' => 'professor-flow@example.test',
            'password' => Hash::make($professorPassword),
            'role' => AccountRole::Instructor,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ]);
        $student = User::factory()->create([
            'name' => 'Student Demo',
            'email' => 'student-flow@students.example.edu',
            'password' => Hash::make($studentPassword),
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ]);
        $record = new SchoolStudentRecord([
            'student_number' => 'SYNTH-V89-0001',
            'institutional_email' => $student->email,
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_at' => now(),
            'linked_at' => now(),
        ]);
        $record->user()->associate($student);
        $record->verifiedBy()->associate($professor);
        $record->save();
        $unassignedPassword = 'synthetic-unassigned-passphrase';
        $unassigned = User::factory()->create([
            'name' => 'Unassigned Student Demo',
            'email' => 'unassigned-flow@students.example.edu',
            'password' => Hash::make($unassignedPassword),
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ]);
        $unassignedRecord = new SchoolStudentRecord([
            'student_number' => 'SYNTH-V89-0002',
            'institutional_email' => $unassigned->email,
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_at' => now(),
            'linked_at' => now(),
        ]);
        $unassignedRecord->user()->associate($unassigned);
        $unassignedRecord->verifiedBy()->associate($professor);
        $unassignedRecord->save();

        $this->post('/login', [
            'email' => $professor->email,
            'password' => $professorPassword,
        ])->assertRedirect('/professor/quizzes');

        $this->post('/professor/quizzes', [
            'title' => 'Functional MVP Demo',
            'course_code' => 'DEMO 101',
            'course_name' => 'Synthetic Assessment Lab',
            'instructions' => 'Save each answer before continuing.',
        ])->assertRedirect();
        $quiz = Quiz::query()->sole();
        $now = CarbonImmutable::now('Asia/Manila');

        $this->put("/professor/quizzes/{$quiz->id}", [
            'title' => 'Functional MVP Demo',
            'course_code' => 'DEMO 101',
            'course_name' => 'Synthetic Assessment Lab',
            'instructions' => 'Save each answer before continuing.',
            'opens_at' => $now->subMinutes(5)->format('Y-m-d\TH:i'),
            'closes_at' => $now->addHours(2)->format('Y-m-d\TH:i'),
            'whole_quiz_duration_seconds' => 1800,
            'per_question_duration_seconds' => null,
            'randomize_questions' => false,
            'randomize_choices' => false,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post("/professor/quizzes/{$quiz->id}/questions", [
            'type' => 'multiple_choice',
            'prompt' => 'Which layer makes the server authoritative?',
            'choices' => [
                ['choice_text' => 'Laravel and PostgreSQL', 'is_correct' => true],
                ['choice_text' => 'The browser clock', 'is_correct' => false],
                ['choice_text' => 'A localStorage flag', 'is_correct' => false],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->post("/professor/quizzes/{$quiz->id}/questions", [
            'type' => 'true_false',
            'prompt' => 'A released V1 result exposes item-level answer keys.',
            'choices' => [
                ['choice_text' => 'True', 'is_correct' => false],
                ['choice_text' => 'False', 'is_correct' => true],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $assignment = new QuizStudentAssignment(['is_active' => true]);
        $assignment->quiz()->associate($quiz);
        $assignment->studentRecord()->associate($record);
        $assignment->assignedBy()->associate($professor);
        $assignment->save();

        $this->post("/professor/quizzes/{$quiz->id}/publish")
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'guarded assessment delivery is enabled'));
        $quiz->refresh();

        $this->get("/professor/quizzes/{$quiz->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->where('quiz.delivery.enabled', true)
                ->where('quiz.delivery.scope', 'isolated_demo')
                ->where('quiz.assignments.directStudentCount', 1));

        $this->delete('/logout')->assertRedirect('/login');
        $this->post('/login', [
            'email' => $unassigned->email,
            'password' => $unassignedPassword,
        ])->assertRedirect('/student/dashboard');
        $this->get("/student/quizzes/join/{$quiz->access_code}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('preflight.state', 'unavailable')
                ->where('preflight.canStart', false)
                ->where('preflight.quiz', null));
        $this->assertDatabaseCount('quiz_attempts', 0);

        $this->delete('/logout')->assertRedirect('/login');
        $this->post('/login', [
            'email' => $student->email,
            'password' => $studentPassword,
        ])->assertRedirect('/student/dashboard');

        $this->get("/student/quizzes/join/{$quiz->access_code}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('preflight.state', 'information_available')
                ->where('preflight.canStart', true)
                ->missing('preflight.quiz.questions')
                ->missing('preflight.quiz.answerKey'));
        $this->post("/student/quizzes/{$quiz->id}/attempts")->assertRedirect();

        $attempt = QuizAttempt::query()->with('questionSnapshots')->sole();
        $first = $attempt->questionSnapshots->firstWhere('position', 1);
        $second = $attempt->questionSnapshots->firstWhere('position', 2);

        $this->post("/student/attempts/{$attempt->id}/answers", [
            'question_snapshot_id' => $first->id,
            'selected_choice_id' => $first->correct_choice_id,
            'expected_version' => 0,
            'idempotency_key' => 'demo-flow-first-answer',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->post("/student/attempts/{$attempt->id}/advance", [
            'expected_version' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $wrongChoice = collect($second->choices)
            ->first(fn (array $choice): bool => $choice['id'] !== $second->correct_choice_id)['id'];
        $this->post("/student/attempts/{$attempt->id}/answers", [
            'question_snapshot_id' => $second->id,
            'selected_choice_id' => $wrongChoice,
            'expected_version' => 2,
            'idempotency_key' => 'demo-flow-second-answer',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->post("/student/attempts/{$attempt->id}/submit", [
            'expected_version' => 3,
            'confirmed' => true,
        ])->assertRedirect("/student/attempts/{$attempt->id}");

        $this->get("/student/attempts/{$attempt->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('attempt.status', 'submitted')
                ->where('attempt.result.state', 'withheld')
                ->missing('attempt.result.earnedPoints')
                ->missing('attempt.result.correctAnswers'));
        $this->assertDatabaseHas('attempt_results', [
            'attempt_id' => $attempt->id,
            'status' => 'scored',
            'earned_points' => 1,
            'maximum_points' => 2,
        ]);

        $this->delete('/logout');
        $this->post('/login', [
            'email' => $professor->email,
            'password' => $professorPassword,
        ])->assertRedirect('/professor/quizzes');
        $this->get("/professor/quizzes/{$quiz->id}/results")
            ->assertInertia(fn (Assert $page) => $page
                ->where('attempts.0.earnedPoints', 1)
                ->where('attempts.0.maximumPoints', 2)
                ->where('attempts.0.canRelease', true));
        $this->post("/professor/quizzes/{$quiz->id}/attempts/{$attempt->id}/release")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->delete('/logout');
        $this->post('/login', [
            'email' => $student->email,
            'password' => $studentPassword,
        ])->assertRedirect('/student/dashboard');
        $this->get("/student/attempts/{$attempt->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('attempt.result.state', 'released')
                ->where('attempt.result.earnedPoints', 1)
                ->where('attempt.result.maximumPoints', 2)
                ->missing('attempt.result.itemResults')
                ->missing('attempt.result.correctAnswers'));

        $this->assertSame(1, AttemptResult::query()->where('attempt_id', $attempt->id)->count());
        $this->assertSame(1, AssessmentAuditEvent::query()->where('event_type', 'result_released')->count());
    }

    public function test_demo_command_refuses_public_schema_and_non_postgresql_provisioning(): void
    {
        $this->artisan('markado:demo provision --schema=public')
            ->expectsOutputToContain('public and other schemas are refused')
            ->assertFailed();

        if (config('database.default') !== 'pgsql') {
            $this->artisan('markado:demo provision --schema=markado_demo_v89_sqlite_refusal')
                ->expectsOutputToContain('requires the PostgreSQL connection')
                ->assertFailed();
        }
    }
}
