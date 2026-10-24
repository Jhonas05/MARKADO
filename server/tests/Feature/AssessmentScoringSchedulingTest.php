<?php

namespace Tests\Feature;

use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\ResultReleaseManager;
use App\Assessment\Contracts\ScoreCalculator;
use App\Assessment\Data\ScoreSummary;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptResult;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizChoice;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\QuizPublicationService;
use DateTimeImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsAssessmentFixtures;
use Tests\Support\MutableAuthoritativeClock;
use Tests\TestCase;

class AssessmentScoringSchedulingTest extends TestCase
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

    #[DataProvider('objectiveScoreCases')]
    public function test_objective_scoring_uses_immutable_snapshot_keys(
        array $answerKinds,
        int $expectedEarned,
    ): void {
        [, $student, $quiz] = $this->assessmentFixture(questionCount: 3);
        $session = app(AttemptSessionManager::class)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->with('questionSnapshots')->findOrFail($session->attemptId);

        foreach ($attempt->questionSnapshots as $index => $snapshot) {
            $kind = $answerKinds[$index] ?? 'unanswered';
            if ($kind === 'unanswered') {
                continue;
            }
            $choiceId = $kind === 'correct'
                ? $snapshot->correct_choice_id
                : collect($snapshot->choices)->first(
                    fn (array $choice): bool => $choice['id'] !== $snapshot->correct_choice_id,
                )['id'];
            $answer = new AttemptAnswer([
                'selected_choice_id' => $choiceId,
                'version' => $index + 1,
                'saved_at' => $this->clock->now(),
            ]);
            $answer->attempt()->associate($attempt);
            $answer->questionSnapshot()->associate($snapshot);
            $answer->save();
        }

        $attempt->forceFill([
            'status' => 'submitted',
            'submitted_at' => $this->clock->now(),
            'finalized_at' => $this->clock->now(),
            'finalization_reason' => 'student_submitted',
            'terminal_receipt_id' => (string) Str::uuid(),
            'progress_state' => 'finalized',
        ])->save();

        $first = app(ScoreCalculator::class)->calculate($attempt);
        $second = app(ScoreCalculator::class)->calculate($attempt);

        $this->assertSame($expectedEarned, $first->earnedPoints);
        $this->assertSame(3, $first->availablePoints);
        $this->assertEquals($first, $second);
        $this->assertDatabaseCount('attempt_results', 1);
        $this->assertDatabaseHas('attempt_results', [
            'attempt_id' => $attempt->id,
            'earned_points' => $expectedEarned,
            'maximum_points' => 3,
            'status' => 'scored',
        ]);
    }

    public static function objectiveScoreCases(): array
    {
        return [
            'zero of three' => [['incorrect', 'incorrect', 'incorrect'], 0],
            'three of three' => [['correct', 'correct', 'correct'], 3],
            'unanswered stays zero' => [['correct', 'unanswered', 'incorrect'], 1],
        ];
    }

    public function test_later_published_revision_cannot_change_historical_snapshot_score(): void
    {
        [$owner, $student, $quiz] = $this->assessmentFixture(questionCount: 1, randomize: false);
        $session = app(AttemptSessionManager::class)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->with('questionSnapshots')->findOrFail($session->attemptId);
        $snapshot = $attempt->questionSnapshots->first();
        $answer = new AttemptAnswer([
            'selected_choice_id' => $snapshot->correct_choice_id,
            'version' => 1,
            'saved_at' => $this->clock->now(),
        ]);
        $answer->attempt()->associate($attempt);
        $answer->questionSnapshot()->associate($snapshot);
        $answer->save();

        $draft = app(QuizPublicationService::class)->createDraftFromPublished($owner, $quiz);
        $draftQuestion = $draft->questions()->with('choices')->firstOrFail();
        foreach ($draftQuestion->choices as $index => $choice) {
            $choice->update(['is_correct' => $index === 1]);
        }
        app(QuizPublicationService::class)->publish($owner, $quiz);

        $attempt->forceFill([
            'status' => 'submitted',
            'submitted_at' => $this->clock->now(),
            'finalized_at' => $this->clock->now(),
            'finalization_reason' => 'student_submitted',
            'terminal_receipt_id' => (string) Str::uuid(),
            'progress_state' => 'finalized',
        ])->save();

        $score = app(ScoreCalculator::class)->calculate($attempt);

        $this->assertSame(1, $score->earnedPoints);
        $this->assertSame($snapshot->correct_choice_id, $attempt->questionSnapshots()->firstOrFail()->correct_choice_id);
    }

    public function test_scoring_failure_preserves_terminal_receipt_and_blocks_release(): void
    {
        [$owner, $student, $quiz] = $this->assessmentFixture(questionCount: 1, questionSeconds: null);
        $this->app->instance(ScoreCalculator::class, new class implements ScoreCalculator
        {
            public function calculate(QuizAttempt $attempt): ScoreSummary
            {
                throw new DomainException('Synthetic scoring failure.');
            }
        });
        $session = app(AttemptSessionManager::class)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);

        $receipt = app(AttemptFinalizer::class)->submitByStudent($attempt);

        $this->assertNotNull($receipt->receiptId);
        $this->assertSame('submitted', $attempt->fresh()->status);
        $this->assertDatabaseHas('attempt_results', [
            'attempt_id' => $attempt->id,
            'status' => 'failed',
            'failure_code' => 'scoring_exception',
        ]);

        try {
            app(ResultReleaseManager::class)->release($attempt->fresh(), $owner);
            $this->fail('A failed score must not be released.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('valid score', $exception->getMessage());
        }

        $this->actingAs($student)->get("/student/attempts/{$attempt->id}")
            ->assertInertia(fn ($page) => $page
                ->where('attempt.result.state', 'processing_failed')
                ->missing('attempt.result.earnedPoints')
                ->missing('attempt.result.maximumPoints'));
    }

    public function test_professor_schedule_inputs_are_interpreted_in_asia_manila_and_snapshotted(): void
    {
        $owner = User::factory()->create([
            'role' => AccountRole::Instructor,
            'account_status' => AccountStatus::Active,
        ]);
        $quiz = new Quiz([
            'title' => 'Timezone-safe draft',
            'course_code' => 'TIME 701',
            'course_name' => 'Scheduling',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = 'TIME2345';
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        $question = new QuizQuestion([
            'type' => QuestionType::TrueFalse,
            'prompt' => 'Laravel is authoritative for assessment time.',
            'position' => 1,
        ]);
        $question->quiz()->associate($quiz);
        $question->save();
        foreach ([['True', true], ['False', false]] as $position => [$text, $correct]) {
            $choice = new QuizChoice([
                'choice_text' => $text,
                'position' => $position + 1,
                'is_correct' => $correct,
            ]);
            $choice->question()->associate($question);
            $choice->save();
        }

        $payload = [
            'title' => $quiz->title,
            'course_code' => $quiz->course_code,
            'course_name' => $quiz->course_name,
            'instructions' => 'Synthetic only.',
            'opens_at' => '2026-10-25T09:00',
            'closes_at' => '2026-10-25T10:00',
            'whole_quiz_duration_seconds' => 1200,
            'per_question_duration_seconds' => 45,
            'randomize_questions' => true,
            'randomize_choices' => true,
        ];

        $this->actingAs($owner)->put("/professor/quizzes/{$quiz->id}", [
            ...$payload,
            'opens_at' => '2026-10-25T10:00',
            'closes_at' => '2026-10-25T09:00',
        ])->assertSessionHasErrors('closes_at');

        $this->actingAs($owner)->put("/professor/quizzes/{$quiz->id}", $payload)->assertRedirect();
        $quiz->refresh();
        $this->assertSame('2026-10-25T01:00:00+00:00', $quiz->opens_at->format('Y-m-d\TH:i:sP'));
        $this->assertSame('2026-10-25T02:00:00+00:00', $quiz->closes_at->format('Y-m-d\TH:i:sP'));

        $revision = app(QuizPublicationService::class)->publish($owner, $quiz);
        $this->assertSame('2026-10-25T01:00:00+00:00', $revision->opens_at->format('Y-m-d\TH:i:sP'));
        $this->assertSame(1200, $revision->whole_quiz_duration_seconds);
        $this->assertTrue($revision->randomize_questions);

        $this->actingAs($owner)->put("/professor/quizzes/{$quiz->id}", [
            ...$payload,
            'opens_at' => '2026-10-25T10:00',
            'closes_at' => '2026-10-25T09:00',
        ])->assertForbidden();
    }

    public function test_expiration_command_reconciles_final_question_and_is_repeat_safe(): void
    {
        [, $student, $quiz] = $this->assessmentFixture();
        $session = app(AttemptSessionManager::class)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);
        $this->clock->advanceTo(new DateTimeImmutable('2026-10-24T01:02:30+00:00'));

        $this->artisan('assessment:reconcile-expired --batch=10')
            ->expectsOutputToContain('Reconciled 1 assessment attempt')
            ->assertSuccessful();

        $attempt->refresh();
        $receipt = $attempt->terminal_receipt_id;
        $this->assertSame('final_question_expired', $attempt->finalization_reason);
        $this->assertSame('2026-10-24T01:02:00+00:00', $attempt->submitted_at->format('Y-m-d\TH:i:sP'));
        $this->assertSame('scored', AttemptResult::query()->where('attempt_id', $attempt->id)->value('status'));

        $this->artisan('assessment:reconcile-expired --batch=10')
            ->expectsOutputToContain('Reconciled 0 assessment attempt')
            ->assertSuccessful();
        $this->assertSame($receipt, $attempt->fresh()->terminal_receipt_id);
    }
}
