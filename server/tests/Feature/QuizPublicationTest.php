<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\QuestionType;
use App\Enums\QuizRevisionStatus;
use App\Enums\QuizStatus;
use App\Models\AttemptQuestionSnapshot;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizChoice;
use App\Models\QuizQuestion;
use App\Models\QuizRevision;
use App\Models\User;
use App\Services\QuizPublicationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

class QuizPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_publish_a_valid_immutable_revision(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/publish")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $quiz->refresh();
        $revision = $quiz->currentPublishedRevision()->with('questions.choices')->sole();

        $this->assertSame(QuizStatus::Published, $quiz->status);
        $this->assertSame(QuizRevisionStatus::Published, $revision->status);
        $this->assertSame(1, $revision->revision_number);
        $this->assertSame('Which organelle produces most cellular ATP?', $revision->questions->sole()->prompt);
        $this->assertCount(1, $revision->questions->sole()->choices->where('is_correct', true));
        $this->assertDatabaseHas('quiz_publication_events', [
            'quiz_id' => $quiz->id,
            'quiz_revision_id' => $revision->id,
            'actor_id' => $owner->id,
            'event_type' => 'published',
        ]);
    }

    public function test_non_owner_cannot_publish_or_create_a_revision_for_another_instructor(): void
    {
        $owner = $this->instructor();
        $other = $this->instructor();
        $quiz = $this->quizFor($owner);

        $this->actingAs($other)->post("/professor/quizzes/{$quiz->id}/publish")->assertForbidden();
        $this->publish($owner, $quiz);
        $this->actingAs($other)->post("/professor/quizzes/{$quiz->id}/draft-revisions")->assertForbidden();

        $this->assertDatabaseCount('quiz_revisions', 1);
    }

    public function test_invalid_or_empty_questions_block_publication(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner, false);

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/publish")
            ->assertRedirect()
            ->assertSessionHasErrors('publication');

        $this->assertSame(QuizStatus::Draft, $quiz->fresh()->status);
        $this->assertDatabaseCount('quiz_revisions', 0);
        $this->assertDatabaseCount('quiz_publication_events', 0);
    }

    public function test_published_revision_questions_and_answer_keys_are_immutable(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $revision = $this->publish($owner, $quiz)->load('questions.choices');
        $question = $revision->questions->sole();
        $choice = $question->choices->first();

        foreach ([
            fn () => $revision->update(['title' => 'Silently changed']),
            fn () => $question->update(['prompt' => 'Silently changed']),
            fn () => $choice->update(['is_correct' => ! $choice->is_correct]),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Published content mutation should throw.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('immutable', strtolower($exception->getMessage()));
            }
        }

        $this->actingAs($owner)
            ->put("/professor/quizzes/{$quiz->id}", $this->quizPayload())
            ->assertForbidden();
        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/questions", $this->questionPayload())
            ->assertForbidden();

        $this->assertSame('Cell Biology Checkpoint', $revision->fresh()->title);
        $this->assertSame('Which organelle produces most cellular ATP?', $question->fresh()->prompt);
    }

    public function test_editing_a_published_quiz_creates_an_independent_draft_revision(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $published = $this->publish($owner, $quiz)->load('questions.choices');

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/draft-revisions")
            ->assertRedirect();

        $quiz->refresh();
        $draft = $quiz->activeDraftRevision()->with('questions.choices')->sole();
        $draftQuestion = $draft->questions->sole();

        $this->actingAs($owner)
            ->get("/professor/quizzes/{$quiz->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->where('quiz.authoring.mode', 'revision')
                ->where('quiz.authoring.editable', true)
                ->where('quiz.authoring.revisionNumber', 2)
                ->where('quiz.publication.currentRevisionNumber', 1)
                ->where('quiz.publication.hasUnpublishedChanges', true)
                ->where('quiz.access.enabled', true)
                ->where('quiz.assignments.managementEnabled', false)
                ->has('quiz.questions.0.choices.0.isCorrect'));

        $payload = $this->questionPayload();
        $payload['prompt'] = 'Which organelle is responsible for most aerobic respiration?';
        $payload['choices'] = $draftQuestion->choices->map(fn ($choice): array => [
            'id' => $choice->id,
            'choice_text' => $choice->choice_text,
            'is_correct' => $choice->is_correct,
        ])->all();

        $this->actingAs($owner)
            ->put("/professor/quizzes/{$quiz->id}/revisions/{$draft->id}", [
                ...$this->quizPayload(),
                'title' => 'Cell Biology Revision Two',
            ])
            ->assertRedirect();
        $this->actingAs($owner)
            ->put("/professor/quizzes/{$quiz->id}/revisions/{$draft->id}/questions/{$draftQuestion->id}", $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(QuizRevisionStatus::Published, $published->fresh()->status);
        $this->assertSame('Cell Biology Checkpoint', $published->fresh()->title);
        $this->assertSame(
            'Which organelle produces most cellular ATP?',
            $published->questions()->sole()->prompt,
        );
        $this->assertSame('Cell Biology Revision Two', $draft->fresh()->title);
        $this->assertNotSame($published->questions->sole()->id, $draftQuestion->id);
        $this->assertSame($published->id, $draft->source_revision_id);
    }

    public function test_publishing_a_new_revision_preserves_attempt_revision_and_snapshot_mapping(): void
    {
        $owner = $this->instructor();
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $quiz = $this->quizFor($owner);
        $first = $this->publish($owner, $quiz)->load('questions.choices');
        $firstQuestion = $first->questions->sole();

        $attempt = new QuizAttempt(['status' => 'in_progress']);
        $attempt->quiz()->associate($quiz);
        $attempt->revision()->associate($first);
        $attempt->student()->associate($student);
        $attempt->save();
        $snapshot = new AttemptQuestionSnapshot([
            'source_revision_question_id' => $firstQuestion->id,
            'position' => 1,
            'type' => $firstQuestion->type,
            'prompt' => $firstQuestion->prompt,
            'choices' => $firstQuestion->choices->map(fn ($choice): array => [
                'id' => $choice->id,
                'text' => $choice->choice_text,
            ])->all(),
            'correct_choice_id' => $firstQuestion->choices->firstWhere('is_correct', true)->id,
        ]);
        $snapshot->attempt()->associate($attempt);
        $snapshot->save();

        $draft = $this->createDraft($owner, $quiz);
        $draft->title = 'New Current Revision';
        $draft->save();
        $second = $this->publish($owner, $quiz->fresh());

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($second->id, $quiz->fresh()->current_published_revision_id);
        $this->assertSame($first->id, $attempt->fresh()->quiz_revision_id);
        $this->assertSame($firstQuestion->id, $snapshot->fresh()->source_revision_question_id);
        $this->assertSame('Which organelle produces most cellular ATP?', $snapshot->fresh()->prompt);
    }

    public function test_unpublish_is_repeat_safe_and_blocks_student_preflight_without_deleting_history(): void
    {
        $owner = $this->instructor();
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $quiz = $this->quizFor($owner);
        $revision = $this->publish($owner, $quiz);

        $this->actingAs($owner)->post("/professor/quizzes/{$quiz->id}/unpublish")->assertRedirect();
        $this->actingAs($owner)->post("/professor/quizzes/{$quiz->id}/unpublish")->assertRedirect();

        $this->assertSame(QuizStatus::Unpublished, $quiz->fresh()->status);
        $this->assertSame($revision->id, $quiz->fresh()->current_published_revision_id);
        $this->assertDatabaseCount('quiz_revisions', 1);
        $this->assertDatabaseCount('quiz_publication_events', 2);
        $this->actingAs($student)
            ->get("/student/quizzes/join/{$quiz->access_code}")
            ->assertInertia(fn ($page) => $page
                ->where('preflight.state', 'unavailable')
                ->where('preflight.quiz', null));
        $this->assertDatabaseCount('quiz_attempts', 0);

        $this->actingAs($owner)->post("/professor/quizzes/{$quiz->id}/publish")->assertRedirect();
        $this->actingAs($owner)->post("/professor/quizzes/{$quiz->id}/publish")->assertRedirect();
        $this->assertSame(QuizStatus::Published, $quiz->fresh()->status);
        $this->assertSame($revision->id, $quiz->fresh()->current_published_revision_id);
        $this->assertDatabaseCount('quiz_revisions', 1);
        $this->assertDatabaseCount('quiz_publication_events', 3);
    }

    public function test_repeated_publish_and_draft_requests_are_idempotent_under_row_lock(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);

        $first = $this->publish($owner, $quiz);
        $same = $this->publish($owner, $quiz->fresh());
        $draft = $this->createDraft($owner, $quiz->fresh());
        $sameDraft = $this->createDraft($owner, $quiz->fresh());

        $this->assertSame($first->id, $same->id);
        $this->assertSame($draft->id, $sameDraft->id);
        $this->assertDatabaseCount('quiz_revisions', 2);
        $this->assertDatabaseHas('quiz_revisions', [
            'quiz_id' => $quiz->id,
            'revision_number' => 1,
            'status' => QuizRevisionStatus::Published->value,
        ]);
        $this->assertDatabaseHas('quiz_revisions', [
            'quiz_id' => $quiz->id,
            'revision_number' => 2,
            'status' => QuizRevisionStatus::Draft->value,
        ]);
    }

    public function test_postgresql_rejects_direct_published_revision_mutation_below_the_model_layer(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL trigger verification.');
        }

        $owner = $this->instructor();
        $revision = $this->publish($owner, $this->quizFor($owner))->load('questions.choices');
        $question = $revision->questions->sole();

        foreach ([
            fn () => DB::table('quiz_revisions')->where('id', $revision->id)->update(['title' => 'Bypass']),
            fn () => DB::table('quiz_revision_questions')->where('id', $question->id)->update(['prompt' => 'Bypass']),
        ] as $mutation) {
            try {
                DB::transaction($mutation);
                $this->fail('The PostgreSQL immutability trigger should reject direct mutation.');
            } catch (QueryException $exception) {
                $this->assertSame('55000', $exception->getCode());
            }
        }
    }

    private function instructor(): User
    {
        return User::factory()->create(['role' => AccountRole::Instructor]);
    }

    private function quizFor(User $owner, bool $withQuestion = true): Quiz
    {
        $quiz = new Quiz($this->quizPayload());
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = Str::upper(Str::random(8));
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        if ($withQuestion) {
            $question = new QuizQuestion([
                'type' => QuestionType::MultipleChoice,
                'prompt' => 'Which organelle produces most cellular ATP?',
                'position' => 1,
            ]);
            $question->quiz()->associate($quiz);
            $question->save();
            foreach ($this->questionPayload()['choices'] as $index => $data) {
                $choice = new QuizChoice([
                    'choice_text' => $data['choice_text'],
                    'position' => $index + 1,
                    'is_correct' => $data['is_correct'],
                ]);
                $choice->question()->associate($question);
                $choice->save();
            }
        }

        return $quiz;
    }

    private function publish(User $owner, Quiz $quiz): QuizRevision
    {
        return app(QuizPublicationService::class)->publish($owner, $quiz);
    }

    private function createDraft(User $owner, Quiz $quiz): QuizRevision
    {
        return app(QuizPublicationService::class)->createDraftFromPublished($owner, $quiz);
    }

    private function quizPayload(): array
    {
        return [
            'title' => 'Cell Biology Checkpoint',
            'course_code' => 'BIO 104',
            'course_name' => 'General Biology',
            'instructions' => 'Choose the best answer.',
        ];
    }

    private function questionPayload(): array
    {
        return [
            'type' => QuestionType::MultipleChoice->value,
            'prompt' => 'Which organelle produces most cellular ATP?',
            'choices' => [
                ['choice_text' => 'Nucleus', 'is_correct' => false],
                ['choice_text' => 'Mitochondrion', 'is_correct' => true],
                ['choice_text' => 'Ribosome', 'is_correct' => false],
                ['choice_text' => 'Golgi apparatus', 'is_correct' => false],
            ],
        ];
    }
}
