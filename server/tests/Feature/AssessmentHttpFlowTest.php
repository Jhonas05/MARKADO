<?php

namespace Tests\Feature;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Models\AssessmentAuditEvent;
use App\Models\AttemptResult;
use App\Models\QuizAttempt;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsAssessmentFixtures;
use Tests\Support\MutableAuthoritativeClock;
use Tests\TestCase;

class AssessmentHttpFlowTest extends TestCase
{
    use BuildsAssessmentFixtures;
    use RefreshDatabase;

    private MutableAuthoritativeClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('assessment.routes_enabled', true);
        config()->set('assessment.expiration_enabled', true);
        $this->clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-24T01:00:00+00:00'));
        $this->app->instance(AuthoritativeClock::class, $this->clock);
    }

    public function test_real_gated_flow_starts_resumes_saves_expires_scores_and_releases_without_leaking_keys(): void
    {
        [$owner, $student, $quiz] = $this->assessmentFixture();

        $this->actingAs($student)
            ->post("/student/quizzes/{$quiz->id}/attempts")
            ->assertRedirect();
        $attempt = QuizAttempt::query()->with('questionSnapshots')->firstOrFail();
        $firstSnapshot = $attempt->questionSnapshots->first();
        $correctFirstChoice = $firstSnapshot->correct_choice_id;

        $this->actingAs($student)
            ->post("/student/quizzes/{$quiz->id}/attempts")
            ->assertRedirect("/student/attempts/{$attempt->id}");
        $this->assertDatabaseCount('quiz_attempts', 1);

        $this->actingAs($student)
            ->get("/student/attempts/{$attempt->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Quizzes/Assessment')
                ->where('attempt.id', $attempt->id)
                ->where('attempt.version', 0)
                ->where('attempt.currentQuestion.id', $firstSnapshot->id)
                ->missing('attempt.currentQuestion.correctChoiceId')
                ->missing('attempt.currentQuestion.answerKey')
                ->missing('attempt.currentQuestion.choices.0.isCorrect')
                ->where('attempt.result.state', 'not_available'));

        $this->actingAs($student)
            ->post("/student/attempts/{$attempt->id}/answers", [
                'question_snapshot_id' => $firstSnapshot->id,
                'selected_choice_id' => $correctFirstChoice,
                'expected_version' => 0,
                'idempotency_key' => 'http-save-first-0001',
                'earned_points' => 999,
            ])->assertRedirect();
        $this->assertDatabaseCount('attempt_results', 0);

        $this->actingAs($student)
            ->get("/student/attempts/{$attempt->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('attempt.currentQuestion.selectedChoiceId', $correctFirstChoice)
                ->where('attempt.version', 1));

        $this->actingAs($student)
            ->post("/student/attempts/{$attempt->id}/advance", ['expected_version' => 1])
            ->assertRedirect();
        $attempt->refresh()->load('questionSnapshots');
        $secondSnapshot = $attempt->questionSnapshots->firstWhere('position', 2);
        $wrongSecondChoice = collect($secondSnapshot->choices)
            ->first(fn (array $choice): bool => $choice['id'] !== $secondSnapshot->correct_choice_id)['id'];

        $this->actingAs($student)
            ->post("/student/attempts/{$attempt->id}/answers", [
                'question_snapshot_id' => $secondSnapshot->id,
                'selected_choice_id' => $wrongSecondChoice,
                'expected_version' => 2,
                'idempotency_key' => 'http-save-second-001',
            ])->assertRedirect();

        $deadline = $attempt->questionStates()
            ->where('question_snapshot_id', $secondSnapshot->id)
            ->value('deadline_at');
        $this->clock->advanceTo(new DateTimeImmutable((string) $deadline));

        $this->actingAs($student)->get("/student/attempts/{$attempt->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('attempt.status', 'submitted')
                ->where('attempt.receipt.reason', 'final_question_expired')
                ->where('attempt.result.state', 'withheld')
                ->missing('attempt.result.earnedPoints')
                ->missing('attempt.result.maximumPoints'));

        $attempt->refresh();
        $result = AttemptResult::query()->where('attempt_id', $attempt->id)->firstOrFail();
        $this->assertSame(1, $result->earned_points);
        $this->assertSame(2, $result->maximum_points);
        $this->assertSame('v1-objective-1-point', $result->scoring_version);

        $otherStudent = User::factory()->create([
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Active,
        ]);
        $this->actingAs($otherStudent)->get("/student/attempts/{$attempt->id}")->assertForbidden();
        $this->actingAs($otherStudent)->post("/student/attempts/{$attempt->id}/answers", [
            'question_snapshot_id' => $secondSnapshot->id,
            'selected_choice_id' => $wrongSecondChoice,
            'expected_version' => $attempt->lock_version,
            'idempotency_key' => 'http-save-second-001',
        ])->assertForbidden();

        $otherInstructor = User::factory()->create([
            'role' => AccountRole::Instructor,
            'account_status' => AccountStatus::Active,
        ]);
        $this->actingAs($otherInstructor)->get("/professor/quizzes/{$quiz->id}/results")->assertForbidden();
        $this->actingAs($otherInstructor)
            ->post("/professor/quizzes/{$quiz->id}/attempts/{$attempt->id}/release")
            ->assertForbidden();
        $administrator = User::factory()->create([
            'role' => AccountRole::AcademicAdministrator,
            'account_status' => AccountStatus::Active,
        ]);
        $this->actingAs($administrator)
            ->post("/professor/quizzes/{$quiz->id}/attempts/{$attempt->id}/release")
            ->assertForbidden();

        $this->actingAs($owner)
            ->get("/professor/quizzes/{$quiz->id}/results")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Professor/Quizzes/Results')
                ->where('attempts.0.earnedPoints', 1)
                ->where('attempts.0.maximumPoints', 2)
                ->where('attempts.0.canRelease', true));

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/attempts/{$attempt->id}/release")
            ->assertRedirect();
        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/attempts/{$attempt->id}/release")
            ->assertRedirect();

        $this->assertSame(1, AssessmentAuditEvent::query()->where('event_type', 'result_released')->count());
        $this->actingAs($student)->get("/student/attempts/{$attempt->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('attempt.result.state', 'released')
                ->where('attempt.result.earnedPoints', 1)
                ->where('attempt.result.maximumPoints', 2)
                ->missing('attempt.result.correctAnswers')
                ->missing('attempt.result.itemResults'));
    }

    public function test_routes_fail_closed_when_server_activation_is_off(): void
    {
        [, $student, $quiz] = $this->assessmentFixture();
        config()->set('assessment.routes_enabled', false);

        $this->actingAs($student)
            ->post("/student/quizzes/{$quiz->id}/attempts")
            ->assertStatus(503);

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_voluntary_submission_with_unanswered_items_stays_narrowly_gated(): void
    {
        [, $student, $quiz] = $this->assessmentFixture();
        $this->actingAs($student)->post("/student/quizzes/{$quiz->id}/attempts");
        $attempt = QuizAttempt::query()->firstOrFail();

        $this->actingAs($student)
            ->post("/student/attempts/{$attempt->id}/submit", [
                'expected_version' => 0,
                'confirmed' => true,
            ])->assertSessionHasErrors('submission');

        $this->assertSame('in_progress', $attempt->fresh()->status);
    }

    public function test_lost_acknowledgement_retry_is_idempotent_and_changed_payload_or_stale_save_is_rejected(): void
    {
        [, $student, $quiz] = $this->assessmentFixture(questionCount: 1);
        $this->actingAs($student)->post("/student/quizzes/{$quiz->id}/attempts");
        $attempt = QuizAttempt::query()->with('questionSnapshots')->firstOrFail();
        $snapshot = $attempt->questionSnapshots->first();
        $choices = collect($snapshot->choices)->pluck('id')->values();
        $payload = [
            'question_snapshot_id' => $snapshot->id,
            'selected_choice_id' => $choices[0],
            'expected_version' => 0,
            'idempotency_key' => 'lost-ack-retry-0001',
        ];

        $this->actingAs($student)->post("/student/attempts/{$attempt->id}/answers", $payload)->assertRedirect();
        $this->actingAs($student)->post("/student/attempts/{$attempt->id}/answers", $payload)
            ->assertRedirect()
            ->assertSessionHas('assessment.replayed', true);

        $this->assertDatabaseCount('attempt_answers', 1);
        $this->assertDatabaseCount('attempt_answer_receipts', 1);

        $this->actingAs($student)->post("/student/attempts/{$attempt->id}/answers", [
            ...$payload,
            'selected_choice_id' => $choices[1],
        ])->assertSessionHasErrors('answer');
        $this->actingAs($student)->post("/student/attempts/{$attempt->id}/answers", [
            ...$payload,
            'idempotency_key' => 'stale-version-save-01',
            'selected_choice_id' => $choices[1],
        ])->assertSessionHasErrors('answer');

        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id' => $attempt->id,
            'selected_choice_id' => $choices[0],
            'version' => 1,
        ]);
    }

    public function test_complete_attempt_can_be_explicitly_confirmed_and_duplicate_submit_returns_one_receipt(): void
    {
        [, $student, $quiz] = $this->assessmentFixture(questionCount: 1, questionSeconds: null);
        $this->actingAs($student)->post("/student/quizzes/{$quiz->id}/attempts");
        $attempt = QuizAttempt::query()->with('questionSnapshots')->firstOrFail();
        $snapshot = $attempt->questionSnapshots->first();
        $this->actingAs($student)->post("/student/attempts/{$attempt->id}/answers", [
            'question_snapshot_id' => $snapshot->id,
            'selected_choice_id' => $snapshot->correct_choice_id,
            'expected_version' => 0,
            'idempotency_key' => 'complete-submit-save1',
        ]);

        $this->actingAs($student)->post("/student/attempts/{$attempt->id}/submit", [
            'expected_version' => 1,
            'confirmed' => true,
        ])->assertRedirect("/student/attempts/{$attempt->id}");
        $receipt = $attempt->fresh()->terminal_receipt_id;

        $this->actingAs($student)->post("/student/attempts/{$attempt->id}/submit", [
            'expected_version' => 1,
            'confirmed' => true,
        ])->assertRedirect("/student/attempts/{$attempt->id}");

        $this->assertSame($receipt, $attempt->fresh()->terminal_receipt_id);
        $this->assertDatabaseCount('attempt_results', 1);
        $this->assertDatabaseHas('attempt_results', [
            'attempt_id' => $attempt->id,
            'earned_points' => 1,
            'maximum_points' => 1,
        ]);
    }
}
