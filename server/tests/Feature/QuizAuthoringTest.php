<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuizAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_an_owner_scoped_private_draft(): void
    {
        $instructor = $this->instructor();

        $response = $this->actingAs($instructor)->post('/professor/quizzes', [
            'title' => 'Cell Biology Checkpoint',
            'course_code' => 'BIO 104',
            'course_name' => 'General Biology',
            'instructions' => 'Choose the best answer.',
        ]);

        $quiz = Quiz::query()->sole();

        $response->assertRedirect("/professor/quizzes/{$quiz->id}/edit");
        $this->assertSame($instructor->id, $quiz->owner_id);
        $this->assertSame(QuizStatus::Draft, $quiz->status);
        $this->assertSame('Asia/Manila', $quiz->display_timezone);
        $this->assertTrue(Str::isUuid($quiz->id));
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $quiz->access_code);
    }

    public function test_student_and_administrator_cannot_enter_instructor_authoring_routes(): void
    {
        foreach ([AccountRole::Student, AccountRole::AcademicAdministrator] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post('/professor/quizzes', $this->validQuizPayload())
                ->assertForbidden();
        }

        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_instructor_only_sees_owned_quizzes_and_list_hides_access_codes(): void
    {
        $owner = $this->instructor();
        $other = $this->instructor();
        $ownedQuiz = $this->quizFor($owner, ['title' => 'Owned draft']);
        $this->quizFor($other, ['title' => 'Private to another instructor']);

        $this->actingAs($owner)
            ->get('/professor/quizzes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Professor/Quizzes/Index')
                ->has('quizzes', 1)
                ->where('quizzes.0.id', $ownedQuiz->id)
                ->where('quizzes.0.title', 'Owned draft')
                ->missing('quizzes.0.accessCode')
                ->missing('quizzes.0.access_code')
            );
    }

    public function test_instructor_cannot_read_or_mutate_another_instructors_quiz(): void
    {
        $owner = $this->instructor();
        $intruder = $this->instructor();
        $quiz = $this->quizFor($owner);

        $this->actingAs($intruder)->get("/professor/quizzes/{$quiz->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/professor/quizzes/{$quiz->id}", $this->validQuizPayload())->assertForbidden();
        $this->actingAs($intruder)->delete("/professor/quizzes/{$quiz->id}")->assertForbidden();
        $this->actingAs($intruder)
            ->post("/professor/quizzes/{$quiz->id}/questions", $this->validQuestionPayload())
            ->assertForbidden();
    }

    public function test_owner_can_add_approved_question_types_with_exactly_one_correct_answer(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/questions", $this->validQuestionPayload())
            ->assertRedirect();

        $question = QuizQuestion::query()->with('choices')->sole();
        $this->assertSame(QuestionType::MultipleChoice, $question->type);
        $this->assertSame(1, $question->position);
        $this->assertCount(4, $question->choices);
        $this->assertCount(1, $question->choices->where('is_correct', true));

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/questions", [
                'type' => QuestionType::TrueFalse->value,
                'prompt' => 'A cell membrane is selectively permeable.',
                'choices' => [
                    ['choice_text' => 'True', 'is_correct' => true],
                    ['choice_text' => 'False', 'is_correct' => false],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'type' => QuestionType::TrueFalse->value,
            'position' => 2,
        ]);
    }

    public function test_owner_can_update_and_delete_a_private_draft(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);

        $this->actingAs($owner)
            ->put("/professor/quizzes/{$quiz->id}", [
                'title' => 'Updated Biology Checkpoint',
                'course_code' => 'BIO 105',
                'course_name' => 'Cell Biology',
                'instructions' => 'Read each item carefully.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Updated Biology Checkpoint',
            'course_code' => 'BIO 105',
        ]);

        $this->actingAs($owner)
            ->delete("/professor/quizzes/{$quiz->id}")
            ->assertRedirect('/professor/quizzes');

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    }

    public function test_question_validation_rejects_ambiguous_answers_and_invalid_true_false_choices(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);

        $this->actingAs($owner)
            ->from("/professor/quizzes/{$quiz->id}/edit")
            ->post("/professor/quizzes/{$quiz->id}/questions", [
                'type' => QuestionType::MultipleChoice->value,
                'prompt' => 'Which structures contain genetic material?',
                'choices' => [
                    ['choice_text' => 'Nucleus', 'is_correct' => true],
                    ['choice_text' => 'Mitochondrion', 'is_correct' => true],
                ],
            ])
            ->assertRedirect("/professor/quizzes/{$quiz->id}/edit")
            ->assertSessionHasErrors('choices');

        $this->actingAs($owner)
            ->post("/professor/quizzes/{$quiz->id}/questions", [
                'type' => QuestionType::TrueFalse->value,
                'prompt' => 'A valid true or false statement.',
                'choices' => [
                    ['choice_text' => 'Yes', 'is_correct' => true],
                    ['choice_text' => 'No', 'is_correct' => false],
                ],
            ])
            ->assertSessionHasErrors('choices');

        $this->assertDatabaseCount('quiz_questions', 0);
    }

    public function test_updating_a_question_preserves_submitted_stable_choice_identifiers(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $this->actingAs($owner)->post(
            "/professor/quizzes/{$quiz->id}/questions",
            $this->validQuestionPayload(),
        );

        $question = QuizQuestion::query()->with('choices')->sole();
        $choiceIds = $question->choices->pluck('id')->all();
        $payload = [
            'type' => QuestionType::MultipleChoice->value,
            'prompt' => 'Which organelle is the main site of aerobic respiration?',
            'choices' => $question->choices->reverse()->values()->map(fn ($choice, $index): array => [
                'id' => $choice->id,
                'choice_text' => $choice->choice_text,
                'is_correct' => $index === 0,
            ])->all(),
        ];

        $this->actingAs($owner)
            ->put("/professor/quizzes/{$quiz->id}/questions/{$question->id}", $payload)
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            $choiceIds,
            $question->fresh()->choices()->pluck('id')->all(),
        );
        $this->assertSame(
            array_reverse($choiceIds),
            $question->fresh()->choices()->pluck('id')->all(),
        );
    }

    public function test_deleting_a_question_keeps_remaining_positions_contiguous(): void
    {
        $owner = $this->instructor();
        $quiz = $this->quizFor($owner);
        $this->actingAs($owner)->post(
            "/professor/quizzes/{$quiz->id}/questions",
            $this->validQuestionPayload(),
        );
        $secondPayload = $this->validQuestionPayload();
        $secondPayload['prompt'] = 'Which structure controls movement into and out of a cell?';
        $this->actingAs($owner)->post(
            "/professor/quizzes/{$quiz->id}/questions",
            $secondPayload,
        );
        $firstQuestion = $quiz->questions()->firstOrFail();

        $this->actingAs($owner)
            ->delete("/professor/quizzes/{$quiz->id}/questions/{$firstQuestion->id}")
            ->assertRedirect();

        $this->assertDatabaseCount('quiz_questions', 1);
        $this->assertSame([1], $quiz->questions()->pluck('position')->all());
    }

    public function test_nested_question_route_rejects_a_question_from_another_quiz(): void
    {
        $owner = $this->instructor();
        $firstQuiz = $this->quizFor($owner);
        $secondQuiz = $this->quizFor($owner);
        $this->actingAs($owner)->post(
            "/professor/quizzes/{$secondQuiz->id}/questions",
            $this->validQuestionPayload(),
        );
        $question = $secondQuiz->questions()->with('choices')->firstOrFail();
        $payload = [
            'type' => $question->type->value,
            'prompt' => $question->prompt,
            'choices' => $question->choices->map(fn ($choice): array => [
                'id' => $choice->id,
                'choice_text' => $choice->choice_text,
                'is_correct' => $choice->is_correct,
            ])->all(),
        ];

        $this->actingAs($owner)
            ->put("/professor/quizzes/{$firstQuiz->id}/questions/{$question->id}", $payload)
            ->assertNotFound();
    }

    public function test_edit_payload_exposes_answer_keys_only_to_the_authorized_owner(): void
    {
        $owner = $this->instructor();
        $other = $this->instructor();
        $quiz = $this->quizFor($owner);
        $this->actingAs($owner)->post(
            "/professor/quizzes/{$quiz->id}/questions",
            $this->validQuestionPayload(),
        );

        $this->actingAs($owner)
            ->get("/professor/quizzes/{$quiz->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->where('quiz.id', $quiz->id)
                ->has('quiz.questions.0.choices.0.isCorrect')
                ->where('quiz.access.enabled', false)
            );

        $this->actingAs($other)
            ->get("/professor/quizzes/{$quiz->id}/edit")
            ->assertForbidden();
    }

    public function test_reserved_access_code_has_no_public_attempt_route(): void
    {
        $quiz = $this->quizFor($this->instructor());

        $this->get("/quiz/{$quiz->access_code}")->assertNotFound();
    }

    private function instructor(): User
    {
        return User::factory()->create(['role' => AccountRole::Instructor]);
    }

    private function quizFor(User $owner, array $attributes = []): Quiz
    {
        $quiz = new Quiz(array_merge($this->validQuizPayload(), $attributes));
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = Str::upper(Str::random(8));
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        return $quiz;
    }

    private function validQuizPayload(): array
    {
        return [
            'title' => 'Cell Biology Checkpoint',
            'course_code' => 'BIO 104',
            'course_name' => 'General Biology',
            'instructions' => 'Choose the best answer.',
        ];
    }

    private function validQuestionPayload(): array
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
