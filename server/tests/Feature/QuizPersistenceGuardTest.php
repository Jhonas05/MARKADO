<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptQuestionSnapshot;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class QuizPersistenceGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_prepares_attempt_resume_snapshots_answers_and_manual_release(): void
    {
        $this->assertTrue(Schema::hasColumns('quiz_attempts', [
            'quiz_id',
            'student_id',
            'quiz_closes_at',
            'session_deadline_at',
            'effective_deadline_at',
            'last_saved_at',
            'submitted_at',
            'finalized_at',
            'finalization_reason',
            'result_released_at',
            'result_released_by',
        ]));
        $this->assertTrue(Schema::hasColumns('attempt_question_snapshots', [
            'attempt_id',
            'source_question_id',
            'type',
            'prompt',
            'choices',
            'correct_choice_id',
        ]));
        $this->assertTrue(Schema::hasColumns('attempt_answers', [
            'attempt_id',
            'question_snapshot_id',
            'selected_choice_id',
            'saved_at',
        ]));
    }

    public function test_database_enforces_one_attempt_per_student_per_quiz(): void
    {
        [$quiz, $student] = $this->quizAndStudent();
        $this->attemptFor($quiz, $student);

        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->expectException(QueryException::class);

        $this->attemptFor($quiz, $student);
    }

    public function test_answer_cannot_reference_a_snapshot_from_another_attempt(): void
    {
        [$quiz, $student] = $this->quizAndStudent();
        $otherStudent = User::factory()->create(['role' => AccountRole::Student]);
        $attempt = $this->attemptFor($quiz, $student);
        $otherAttempt = $this->attemptFor($quiz, $otherStudent);
        $snapshot = $this->snapshotFor($attempt);

        $this->assertDatabaseCount('attempt_answers', 0);
        $this->expectException(QueryException::class);

        DB::table('attempt_answers')->insert([
            'id' => (string) Str::uuid(),
            'attempt_id' => $otherAttempt->id,
            'question_snapshot_id' => $snapshot->id,
            'selected_choice_id' => null,
            'saved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_snapshot_model_rejects_updates_after_creation(): void
    {
        [$quiz, $student] = $this->quizAndStudent();
        $snapshot = $this->snapshotFor($this->attemptFor($quiz, $student));

        $this->expectException(LogicException::class);
        $snapshot->update(['prompt' => 'Mutated prompt']);
    }

    public function test_valid_saved_answer_is_linked_to_its_attempt_snapshot(): void
    {
        [$quiz, $student] = $this->quizAndStudent();
        $attempt = $this->attemptFor($quiz, $student);
        $snapshot = $this->snapshotFor($attempt);
        $answer = new AttemptAnswer(['saved_at' => now()]);
        $answer->attempt()->associate($attempt);
        $answer->questionSnapshot()->associate($snapshot);
        $answer->save();

        $this->assertTrue($answer->exists);
        $this->assertSame($attempt->id, $answer->attempt_id);
        $this->assertSame($snapshot->id, $answer->question_snapshot_id);
    }

    private function quizAndStudent(): array
    {
        $owner = User::factory()->create(['role' => AccountRole::Instructor]);
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $quiz = new Quiz([
            'title' => 'Synthetic Test Quiz',
            'course_code' => 'TST 101',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = Str::upper(Str::random(8));
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        return [$quiz, $student];
    }

    private function attemptFor(Quiz $quiz, User $student): QuizAttempt
    {
        $attempt = new QuizAttempt(['status' => 'in_progress']);
        $attempt->quiz()->associate($quiz);
        $attempt->student()->associate($student);
        $attempt->save();

        return $attempt;
    }

    private function snapshotFor(QuizAttempt $attempt): AttemptQuestionSnapshot
    {
        $snapshot = new AttemptQuestionSnapshot([
            'position' => 1,
            'type' => QuestionType::TrueFalse,
            'prompt' => 'Synthetic immutable statement.',
            'choices' => [
                ['id' => (string) Str::uuid(), 'text' => 'True'],
                ['id' => (string) Str::uuid(), 'text' => 'False'],
            ],
        ]);
        $snapshot->attempt()->associate($attempt);
        $snapshot->save();

        return $snapshot;
    }
}
