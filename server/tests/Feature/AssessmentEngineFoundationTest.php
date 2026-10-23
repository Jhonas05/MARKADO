<?php

namespace Tests\Feature;

use App\Assessment\Data\AnswerSaveCommand;
use App\Assessment\Support\AttemptFinalizationCoordinator;
use App\Assessment\Support\DatabaseAnswerStore;
use App\Assessment\Support\DatabaseAttemptSessionManager;
use App\Assessment\Support\DatabaseQuizEligibilityGate;
use App\Assessment\Support\DatabaseSchoolEligibilitySource;
use App\Assessment\Support\QuestionDeadlineAdvancer;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\QuestionType;
use App\Enums\QuizRevisionStatus;
use App\Enums\QuizStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\AttemptAnswer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizRevision;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use DateTimeImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MutableAuthoritativeClock;
use Tests\TestCase;

class AssessmentEngineFoundationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('deniedStartInstants')]
    public function test_start_window_denies_before_open_and_at_or_after_close(string $instant): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $manager = $this->manager(new MutableAuthoritativeClock(new DateTimeImmutable($instant)));

        $this->expectException(DomainException::class);
        $manager->resumeOrCreate($student, $quiz);
    }

    public static function deniedStartInstants(): array
    {
        return [
            'before opening' => ['2026-10-23T00:59:59+00:00'],
            'exact closing instant' => ['2026-10-23T02:00:00+00:00'],
            'after closing' => ['2026-10-23T02:00:01+00:00'],
        ];
    }

    public function test_start_at_opening_is_inclusive_and_duplicate_start_resumes_stable_state(): void
    {
        [$quiz, $student] = $this->publishedQuiz(randomize: true);
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:00+00:00'));
        $manager = $this->manager($clock);

        $created = $manager->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($created->attemptId);
        $questionOrder = $attempt->questionSnapshots()->pluck('source_revision_question_id')->all();
        $choiceOrder = $attempt->questionSnapshots()->get()->mapWithKeys(
            fn ($snapshot) => [$snapshot->id => collect($snapshot->choices)->pluck('id')->all()],
        )->all();
        $originalSeed = $attempt->permutation_seed;
        $originalDeadline = $attempt->effective_deadline_at;
        $originalQuestionDeadline = $created->questionDeadlineAt;

        $clock->advanceTo(new DateTimeImmutable('2026-10-23T01:00:30+00:00'));
        $resumed = $manager->resumeOrCreate($student, $quiz);
        $attempt->refresh();

        $this->assertFalse($created->resumed);
        $this->assertTrue($resumed->resumed);
        $this->assertSame($created->attemptId, $resumed->attemptId);
        $this->assertSame($originalSeed, $attempt->permutation_seed);
        $this->assertEquals($originalDeadline, $attempt->effective_deadline_at);
        $this->assertEquals($originalQuestionDeadline, $resumed->questionDeadlineAt);
        $this->assertSame($questionOrder, $attempt->questionSnapshots()->pluck('source_revision_question_id')->all());
        $this->assertSame($choiceOrder, $attempt->questionSnapshots()->get()->mapWithKeys(
            fn ($snapshot) => [$snapshot->id => collect($snapshot->choices)->pluck('id')->all()],
        )->all());
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_late_start_receives_only_time_remaining_before_global_close(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:50:00+00:00'));

        $session = $this->manager($clock)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);

        $this->assertSame('2026-10-23T02:10:00+00:00', $attempt->session_deadline_at->format('Y-m-d\TH:i:sP'));
        $this->assertSame('2026-10-23T02:00:00+00:00', $attempt->effective_deadline_at->format('Y-m-d\TH:i:sP'));
        $this->assertSame('2026-10-23T01:51:00+00:00', $session->questionDeadlineAt->format('Y-m-d\TH:i:sP'));
    }

    public function test_question_expiry_advances_from_persisted_logical_deadlines_without_reconnect_grace(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:00+00:00'));
        $manager = $this->manager($clock);
        $created = $manager->resumeOrCreate($student, $quiz);

        $clock->advanceTo(new DateTimeImmutable('2026-10-23T01:02:30+00:00'));
        $resumed = $manager->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($created->attemptId);
        $states = $attempt->questionStates()->with('questionSnapshot')->get()->keyBy(
            fn ($state) => $state->questionSnapshot->position,
        );

        $this->assertSame(3, $resumed->currentQuestionPosition);
        $this->assertSame('locked', $states[1]->status);
        $this->assertSame('locked', $states[2]->status);
        $this->assertSame('active', $states[3]->status);
        $this->assertSame('2026-10-23T01:02:00+00:00', $states[3]->started_at->format('Y-m-d\TH:i:sP'));
        $this->assertSame('2026-10-23T01:03:00+00:00', $states[3]->deadline_at->format('Y-m-d\TH:i:sP'));
    }

    public function test_final_question_expiry_stays_gated_without_inventing_early_submission(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:00+00:00'));
        $manager = $this->manager($clock);
        $session = $manager->resumeOrCreate($student, $quiz);
        $clock->advanceTo(new DateTimeImmutable('2026-10-23T01:03:00+00:00'));

        $resumed = $manager->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);

        $this->assertSame('in_progress', $resumed->status);
        $this->assertNull($resumed->currentQuestionPosition);
        $this->assertSame('awaiting_final_question_policy', $attempt->progress_state);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_simultaneous_question_and_session_deadline_finalizes_the_attempt_once(): void
    {
        [$quiz, $student] = $this->publishedQuiz(wholeSeconds: 60, questionSeconds: 60);
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:00+00:00'));
        $manager = $this->manager($clock);
        $session = $manager->resumeOrCreate($student, $quiz);
        $clock->advanceTo(new DateTimeImmutable('2026-10-23T01:01:00+00:00'));

        $expired = $manager->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);

        $this->assertSame('submitted', $expired->status);
        $this->assertSame('session_expired', $attempt->finalization_reason);
        $this->assertSame('2026-10-23T01:01:00+00:00', $attempt->submitted_at->format('Y-m-d\TH:i:sP'));
        $this->assertNotNull($attempt->terminal_receipt_id);
    }

    public function test_answer_save_acknowledgement_is_versioned_and_idempotent(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:10+00:00'));
        $manager = $this->manager($clock);
        $session = $manager->resumeOrCreate($student, $quiz);
        $snapshot = QuizAttempt::query()->findOrFail($session->attemptId)->questionSnapshots()->firstOrFail();
        $choiceId = $snapshot->choices[0]['id'];
        $command = new AnswerSaveCommand($session->attemptId, $snapshot->id, $choiceId, 'save-request-0001', 0);
        $store = $this->answerStore($clock);

        $first = $store->save($command);
        $replayed = $store->save($command);

        $this->assertSame(1, $first->version);
        $this->assertSame('save-request-0001', $first->idempotencyKey);
        $this->assertFalse($first->replayed);
        $this->assertTrue($replayed->replayed);
        $this->assertEquals($first->savedAt, $replayed->savedAt);
        $this->assertDatabaseCount('attempt_answers', 1);
        $this->assertDatabaseCount('attempt_answer_receipts', 1);
        $this->assertSame($choiceId, AttemptAnswer::query()->firstOrFail()->selected_choice_id);
    }

    public function test_answer_at_exact_question_deadline_is_rejected_and_last_acknowledgement_is_preserved(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:10+00:00'));
        $session = $this->manager($clock)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);
        $snapshot = $attempt->questionSnapshots()->firstOrFail();
        $store = $this->answerStore($clock);
        $acknowledgedChoiceId = $snapshot->choices[0]['id'];
        $store->save(new AnswerSaveCommand(
            $attempt->id,
            $snapshot->id,
            $acknowledgedChoiceId,
            'before-question-deadline',
            0,
        ));
        $clock->advanceTo(new DateTimeImmutable('2026-10-23T01:01:10+00:00'));

        try {
            $store->save(new AnswerSaveCommand(
                $attempt->id,
                $snapshot->id,
                $snapshot->choices[1]['id'],
                'at-question-deadline',
                1,
            ));
            $this->fail('An answer received at the question deadline must be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('state changed', $exception->getMessage());
        }

        $attempt->refresh();
        $this->assertSame(2, $attempt->current_question_position);
        $this->assertSame($acknowledgedChoiceId, AttemptAnswer::query()->firstOrFail()->selected_choice_id);
        $this->assertDatabaseCount('attempt_answers', 1);
        $this->assertDatabaseCount('attempt_answer_receipts', 1);
    }

    public function test_stale_multi_tab_save_and_reused_idempotency_payload_are_rejected(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:10+00:00'));
        $session = $this->manager($clock)->resumeOrCreate($student, $quiz);
        $snapshot = QuizAttempt::query()->findOrFail($session->attemptId)->questionSnapshots()->firstOrFail();
        $store = $this->answerStore($clock);
        $store->save(new AnswerSaveCommand(
            $session->attemptId,
            $snapshot->id,
            $snapshot->choices[0]['id'],
            'save-request-tab-a',
            0,
        ));

        try {
            $store->save(new AnswerSaveCommand(
                $session->attemptId,
                $snapshot->id,
                $snapshot->choices[1]['id'],
                'save-request-tab-b',
                0,
            ));
            $this->fail('A stale version from another tab must not overwrite an acknowledged answer.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('state changed', $exception->getMessage());
        }

        $this->expectException(DomainException::class);
        $store->save(new AnswerSaveCommand(
            $session->attemptId,
            $snapshot->id,
            $snapshot->choices[1]['id'],
            'save-request-tab-a',
            1,
        ));
    }

    public function test_late_save_finalizes_at_authoritative_deadline_and_never_persists_answer(): void
    {
        [$quiz, $student] = $this->publishedQuiz(wholeSeconds: 60, questionSeconds: 120);
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:00+00:00'));
        $session = $this->manager($clock)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);
        $snapshot = $attempt->questionSnapshots()->firstOrFail();
        $clock->advanceTo(new DateTimeImmutable('2026-10-23T01:05:00+00:00'));

        try {
            $this->answerStore($clock)->save(new AnswerSaveCommand(
                $attempt->id,
                $snapshot->id,
                $snapshot->choices[0]['id'],
                'late-save-request',
                0,
            ));
            $this->fail('A late answer must be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('deadline', $exception->getMessage());
        }

        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame('session_expired', $attempt->finalization_reason);
        $this->assertSame('2026-10-23T01:01:00+00:00', $attempt->submitted_at->format('Y-m-d\TH:i:sP'));
        $this->assertDatabaseCount('attempt_answers', 0);
    }

    public function test_duplicate_finalization_returns_one_stable_receipt(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T01:00:00+00:00'));
        $session = $this->manager($clock)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);
        $finalizer = new AttemptFinalizationCoordinator($clock);

        $first = $finalizer->submitByStudent($attempt);
        $second = $finalizer->submitByStudent($attempt);

        $this->assertSame($first->receiptId, $second->receiptId);
        $this->assertFalse($first->alreadyFinalized);
        $this->assertTrue($second->alreadyFinalized);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    private function manager(MutableAuthoritativeClock $clock): DatabaseAttemptSessionManager
    {
        $finalizer = new AttemptFinalizationCoordinator($clock);
        $advancer = new QuestionDeadlineAdvancer($clock);

        return new DatabaseAttemptSessionManager(
            new DatabaseQuizEligibilityGate(new DatabaseSchoolEligibilitySource),
            $clock,
            $finalizer,
            $advancer,
        );
    }

    private function answerStore(MutableAuthoritativeClock $clock): DatabaseAnswerStore
    {
        return new DatabaseAnswerStore(
            $clock,
            new AttemptFinalizationCoordinator($clock),
            new QuestionDeadlineAdvancer($clock),
        );
    }

    private function publishedQuiz(
        int $wholeSeconds = 1200,
        int $questionSeconds = 60,
        bool $randomize = false,
    ): array {
        $owner = User::factory()->create([
            'role' => AccountRole::Instructor,
            'account_status' => AccountStatus::Active,
        ]);
        $student = User::factory()->create([
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Active,
        ]);
        $quiz = new Quiz([
            'title' => 'Trusted assessment fixture',
            'course_code' => 'ENG 501',
            'course_name' => 'Assessment Engine',
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
            'display_timezone' => 'Asia/Manila',
            'opens_at' => new DateTimeImmutable('2026-10-23T01:00:00+00:00'),
            'closes_at' => new DateTimeImmutable('2026-10-23T02:00:00+00:00'),
            'whole_quiz_duration_seconds' => $wholeSeconds,
            'per_question_duration_seconds' => $questionSeconds,
            'randomize_questions' => $randomize,
            'randomize_choices' => $randomize,
        ]);
        $revision->quiz()->associate($quiz);
        $revision->createdBy()->associate($owner);
        $revision->save();

        foreach (range(1, 3) as $position) {
            $question = $revision->questions()->create([
                'type' => QuestionType::MultipleChoice,
                'prompt' => "Synthetic trusted question {$position}?",
                'position' => $position,
            ]);
            foreach (range(1, 3) as $choicePosition) {
                $question->choices()->create([
                    'choice_text' => "Choice {$choicePosition}",
                    'position' => $choicePosition,
                    'is_correct' => $choicePosition === 1,
                ]);
            }
        }

        $revision->status = QuizRevisionStatus::Published;
        $revision->published_at = new DateTimeImmutable('2026-10-22T00:00:00+00:00');
        $revision->publishedBy()->associate($owner);
        $revision->save();
        $quiz->forceFill([
            'status' => QuizStatus::Published,
            'current_published_revision_id' => $revision->id,
        ])->save();

        $record = new SchoolStudentRecord([
            'student_number' => 'S-'.Str::upper(Str::random(10)),
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_at' => new DateTimeImmutable('2026-10-22T00:00:00+00:00'),
        ]);
        $record->user()->associate($student);
        $record->verifiedBy()->associate($owner);
        $record->save();
        $assignment = new QuizStudentAssignment(['is_active' => true]);
        $assignment->quiz()->associate($quiz);
        $assignment->studentRecord()->associate($record);
        $assignment->assignedBy()->associate($owner);
        $assignment->save();

        return [$quiz, $student, $revision];
    }
}
