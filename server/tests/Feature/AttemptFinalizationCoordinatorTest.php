<?php

namespace Tests\Feature;

use App\Assessment\Support\AttemptFinalizationCoordinator;
use App\Enums\AccountRole;
use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptQuestionSnapshot;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use DateTimeImmutable;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MutableAuthoritativeClock;
use Tests\TestCase;

class AttemptFinalizationCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_close_finalization_preserves_acknowledged_answers_and_leaves_others_unanswered(): void
    {
        [$attempt, $clock] = $this->attemptClosingAtTen();
        $answered = $this->snapshotFor($attempt, 1);
        $this->snapshotFor($attempt, 2);
        $choiceId = $answered->choices[0]['id'];
        $answer = new AttemptAnswer([
            'selected_choice_id' => $choiceId,
            'saved_at' => new DateTimeImmutable('2026-10-23T01:59:30+00:00'),
        ]);
        $answer->attempt()->associate($attempt);
        $answer->questionSnapshot()->associate($answered);
        $answer->save();
        $attempt->update(['last_saved_at' => $answer->saved_at]);
        $clock->advanceTo(new DateTimeImmutable('2026-10-23T10:00:00+08:00'));

        $receipt = (new AttemptFinalizationCoordinator($clock))->finalizeAtQuizClose($attempt);

        $this->assertSame('submitted', $receipt->status);
        $this->assertSame('quiz_closed', $receipt->reason);
        $this->assertSame(2, $attempt->questionSnapshots()->count());
        $this->assertSame(1, $attempt->answers()->count());
        $this->assertSame($choiceId, $attempt->answers()->firstOrFail()->selected_choice_id);
    }

    public function test_duplicate_close_finalization_returns_the_same_terminal_record(): void
    {
        [$attempt, $clock, $close] = $this->attemptClosingAtTen();
        $clock->advanceTo($close);
        $coordinator = new AttemptFinalizationCoordinator($clock);

        $first = $coordinator->finalizeAtQuizClose($attempt);
        $second = $coordinator->finalizeAtQuizClose($attempt);

        $this->assertFalse($first->alreadyFinalized);
        $this->assertTrue($second->alreadyFinalized);
        $this->assertSame($first->logicalSubmissionAt->format('U.u'), $second->logicalSubmissionAt->format('U.u'));
        $this->assertSame($first->recordedAt->format('U.u'), $second->recordedAt->format('U.u'));
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_submit_and_close_race_converges_for_both_serialized_lock_orders(): void
    {
        [$manualFirstAttempt, $manualClock, $close] = $this->attemptClosingAtTen();
        $coordinator = new AttemptFinalizationCoordinator($manualClock);
        $manualReceipt = $coordinator->submitByStudent($manualFirstAttempt);
        $manualClock->advanceTo($close);
        $closeRetry = $coordinator->finalizeAtQuizClose($manualFirstAttempt);

        $this->assertSame('student_submitted', $manualReceipt->reason);
        $this->assertSame('student_submitted', $closeRetry->reason);
        $this->assertTrue($closeRetry->alreadyFinalized);

        [$closeFirstAttempt, $closeClock, $secondClose] = $this->attemptClosingAtTen();
        $closeClock->advanceTo($secondClose);
        $secondCoordinator = new AttemptFinalizationCoordinator($closeClock);
        $closeReceipt = $secondCoordinator->finalizeAtQuizClose($closeFirstAttempt);
        $manualRetry = $secondCoordinator->submitByStudent($closeFirstAttempt);

        $this->assertSame('quiz_closed', $closeReceipt->reason);
        $this->assertSame('quiz_closed', $manualRetry->reason);
        $this->assertTrue($manualRetry->alreadyFinalized);
        $this->assertDatabaseCount('quiz_attempts', 2);
    }

    public function test_delayed_worker_preserves_the_logical_closing_deadline(): void
    {
        [$attempt, $clock, $close] = $this->attemptClosingAtTen();
        $workerObservedAt = new DateTimeImmutable('2026-10-23T02:05:00+00:00');
        $clock->advanceTo($workerObservedAt);

        $receipt = (new AttemptFinalizationCoordinator($clock))->finalizeAtQuizClose($attempt);

        $this->assertSame($close->format('U.u'), $receipt->logicalSubmissionAt->format('U.u'));
        $this->assertSame($workerObservedAt->format('U.u'), $receipt->recordedAt->format('U.u'));
    }

    public function test_reconnection_after_close_observes_the_same_persisted_terminal_state(): void
    {
        [$attempt, $clock, $close] = $this->attemptClosingAtTen();
        $clock->advanceTo($close);
        $coordinator = new AttemptFinalizationCoordinator($clock);
        $first = $coordinator->finalizeAtQuizClose($attempt);

        $reloadedAttempt = QuizAttempt::query()->findOrFail($attempt->id);
        $afterReconnect = $coordinator->finalizeAtQuizClose($reloadedAttempt);

        $this->assertSame($first->reason, $afterReconnect->reason);
        $this->assertSame($first->logicalSubmissionAt->format('U.u'), $afterReconnect->logicalSubmissionAt->format('U.u'));
        $this->assertTrue($afterReconnect->alreadyFinalized);
    }

    public function test_close_finalization_cannot_run_early(): void
    {
        [$attempt, $clock] = $this->attemptClosingAtTen();

        $this->expectException(DomainException::class);
        (new AttemptFinalizationCoordinator($clock))->finalizeAtQuizClose($attempt);
    }

    #[DataProvider('invalidEffectiveDeadlines')]
    public function test_postgresql_requires_effective_deadline_to_equal_the_approved_minimum(string $invalidDeadline): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL check-constraint verification requires PostgreSQL.');
        }

        [$attempt] = $this->attemptClosingAtTen();

        $this->expectException(QueryException::class);
        DB::table('quiz_attempts')->where('id', $attempt->id)->update([
            'effective_deadline_at' => new DateTimeImmutable($invalidDeadline),
        ]);
    }

    public static function invalidEffectiveDeadlines(): array
    {
        return [
            'later than global close' => ['2026-10-23T02:00:01+00:00'],
            'not the minimum of stored deadlines' => ['2026-10-23T01:59:59+00:00'],
        ];
    }

    private function attemptClosingAtTen(): array
    {
        $owner = User::factory()->create(['role' => AccountRole::Instructor]);
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $quiz = new Quiz([
            'title' => 'Synthetic D07 quiz',
            'course_code' => 'D07 101',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = Str::upper(Str::random(8));
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        $startedAt = new DateTimeImmutable('2026-10-23T01:50:00+00:00');
        $close = new DateTimeImmutable('2026-10-23T02:00:00+00:00');
        $attempt = new QuizAttempt([
            'status' => 'in_progress',
            'started_at' => $startedAt,
            'quiz_closes_at' => $close,
            'session_deadline_at' => new DateTimeImmutable('2026-10-23T02:10:00+00:00'),
            'effective_deadline_at' => $close,
        ]);
        $attempt->quiz()->associate($quiz);
        $attempt->student()->associate($student);
        $attempt->save();

        return [$attempt, new MutableAuthoritativeClock($startedAt), $close];
    }

    private function snapshotFor(QuizAttempt $attempt, int $position): AttemptQuestionSnapshot
    {
        $choices = [
            ['id' => (string) Str::uuid(), 'text' => 'True'],
            ['id' => (string) Str::uuid(), 'text' => 'False'],
        ];
        $snapshot = new AttemptQuestionSnapshot([
            'position' => $position,
            'type' => QuestionType::TrueFalse,
            'prompt' => "Synthetic statement {$position}.",
            'choices' => $choices,
            'correct_choice_id' => $choices[0]['id'],
        ]);
        $snapshot->attempt()->associate($attempt);
        $snapshot->save();

        return $snapshot;
    }
}
