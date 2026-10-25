<?php

namespace Tests\Feature;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Contracts\QuizEligibilityGate;
use App\Assessment\Data\QuizEligibilityDecision;
use App\Enums\AccountRole;
use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\QuizChoice;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\QuizPublicationService;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\MutableAuthoritativeClock;
use Tests\TestCase;

class StudentQuizPreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_preflight_requires_an_authenticated_student(): void
    {
        $this->get('/student/quizzes/join')->assertRedirect('/login');
        $this->get('/student/quizzes/join/ABCD2345')->assertRedirect('/login');

        foreach ([AccountRole::Instructor, AccountRole::AcademicAdministrator] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/student/quizzes/join')->assertForbidden();
            $this->actingAs($user)->get('/student/quizzes/join/ABCD2345')->assertForbidden();
        }

        $this->assertDatabaseCount('quiz_access_events', 0);
    }

    public function test_student_workspace_links_to_preflight_without_activating_an_attempt(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->get('/workspace')
            ->assertRedirect('/student/dashboard');

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Workspace')
                ->where('quizJoinUrl', route('student.quizzes.join'))
                ->where('assessmentStartAvailable', false)
                ->where('eligibility.hasSchoolRecord', false)
            );

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_join_form_normalizes_valid_codes_and_rejects_invalid_formats(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->from('/student/quizzes/join')
            ->post('/student/quizzes/preflight', ['code' => ' abcd2345 '])
            ->assertRedirect('/student/quizzes/join/ABCD2345');

        $this->actingAs($student)
            ->from('/student/quizzes/join')
            ->post('/student/quizzes/preflight', ['code' => 'short'])
            ->assertRedirect('/student/quizzes/join')
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_unknown_and_draft_codes_share_a_safe_unavailable_contract(): void
    {
        $student = $this->student();
        $draft = $this->quiz(QuizStatus::Draft, 'DRAFT234');

        foreach (['NONE2345', $draft->access_code] as $code) {
            $this->actingAs($student)
                ->get("/student/quizzes/join/{$code}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Student/Quizzes/Preflight')
                    ->where('preflight.state', 'unavailable')
                    ->where('preflight.quiz', null)
                    ->where('preflight.canStart', false)
                    ->missing('preflight.accessCode')
                    ->missing('preflight.questions')
                    ->missing('preflight.answerKey')
                );
        }

        $this->assertDatabaseCount('quiz_access_events', 2);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_published_quiz_remains_closed_without_a_verified_school_assignment(): void
    {
        $student = $this->student();
        $quiz = $this->quiz(QuizStatus::Draft, 'LVEZ2345');
        $this->addProtectedQuestion($quiz);
        $this->publish($quiz);

        $this->actingAs($student)
            ->get('/student/quizzes/join/LVEZ2345')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Quizzes/Preflight')
                ->where('preflight.state', 'unavailable')
                ->where('preflight.quiz', null)
                ->where('preflight.canStart', false)
                ->missing('preflight.title')
                ->missing('preflight.instructions')
                ->missing('preflight.questions')
                ->missing('preflight.choices')
                ->missing('preflight.isCorrect')
            );

        $this->assertDatabaseHas('quiz_access_events', [
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'outcome' => 'unavailable',
            'source' => 'join_link',
        ]);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_safe_information_projection_omits_codes_questions_and_answer_keys(): void
    {
        $this->app->bind(QuizEligibilityGate::class, fn () => new class implements QuizEligibilityGate
        {
            public function evaluate(User $student, Quiz $quiz): QuizEligibilityDecision
            {
                return QuizEligibilityDecision::allowed();
            }
        });

        $student = $this->student();
        $quiz = $this->quiz(QuizStatus::Draft, 'SAFE2345');
        $this->addProtectedQuestion($quiz);
        $this->publish($quiz);

        $this->actingAs($student)
            ->get('/student/quizzes/join/SAFE2345')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Quizzes/Preflight')
                ->where('preflight.state', 'information_available')
                ->where('preflight.canStart', false)
                ->where('preflight.quiz.id', $quiz->id)
                ->where('preflight.quiz.title', 'Cell Biology Checkpoint')
                ->where('preflight.quiz.questionCount', 1)
                ->where('preflight.quiz.displayTimezone', 'Asia/Manila')
                ->missing('preflight.quiz.accessCode')
                ->missing('preflight.quiz.access_code')
                ->missing('preflight.quiz.questions')
                ->missing('preflight.quiz.choices')
                ->missing('preflight.quiz.answerKey')
                ->missing('preflight.quiz.isCorrect')
            );

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_enabled_preflight_allows_start_only_strictly_inside_the_server_window(): void
    {
        $this->app->bind(QuizEligibilityGate::class, fn () => new class implements QuizEligibilityGate
        {
            public function evaluate(User $student, Quiz $quiz): QuizEligibilityDecision
            {
                return QuizEligibilityDecision::allowed();
            }
        });
        config()->set('assessment.routes_enabled', true);
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-24T01:00:00+00:00'));
        $this->app->instance(AuthoritativeClock::class, $clock);

        $student = $this->student();
        $quiz = $this->quiz(QuizStatus::Draft, 'WAND2345');
        $quiz->forceFill([
            'opens_at' => new DateTimeImmutable('2026-10-24T00:59:00+00:00'),
            'closes_at' => new DateTimeImmutable('2026-10-24T01:01:00+00:00'),
        ])->save();
        $this->addProtectedQuestion($quiz);
        $this->publish($quiz);

        $this->actingAs($student)
            ->get('/student/quizzes/join/WAND2345')
            ->assertInertia(fn (Assert $page) => $page
                ->where('preflight.canStart', true)
                ->where('preflight.startMessage', 'Review the details above, then select Start quiz when you are ready.')
            );

        $clock->advanceTo(new DateTimeImmutable('2026-10-24T01:01:00+00:00'));
        $this->actingAs($student)
            ->get('/student/quizzes/join/WAND2345')
            ->assertInertia(fn (Assert $page) => $page
                ->where('preflight.canStart', false)
                ->where('preflight.startMessage', 'This quiz is closed for new attempts.'));

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_access_audit_schema_never_stores_codes_or_protected_content(): void
    {
        $student = $this->student();
        $this->actingAs($student)->get('/student/quizzes/join/UNKNOWN1')->assertOk();

        $columns = Schema::getColumnListing('quiz_access_events');

        $this->assertEqualsCanonicalizing(
            ['id', 'user_id', 'quiz_id', 'outcome', 'source', 'occurred_at'],
            $columns,
        );
        $this->assertDatabaseHas('quiz_access_events', [
            'user_id' => $student->id,
            'quiz_id' => null,
            'outcome' => 'unavailable',
        ]);
    }

    public function test_quiz_join_resolution_is_rate_limited_per_account(): void
    {
        $student = $this->student();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($student)->get('/student/quizzes/join/NONE2345')->assertOk();
        }

        $this->actingAs($student)
            ->get('/student/quizzes/join/NONE2345')
            ->assertTooManyRequests();
    }

    private function student(): User
    {
        return User::factory()->create(['role' => AccountRole::Student]);
    }

    private function quiz(QuizStatus $status, string $code): Quiz
    {
        $quiz = new Quiz([
            'title' => 'Cell Biology Checkpoint',
            'course_code' => 'BIO 104',
            'course_name' => 'General Biology',
            'instructions' => 'Read every item carefully.',
        ]);
        $quiz->owner()->associate(User::factory()->create(['role' => AccountRole::Instructor]));
        $quiz->status = $status;
        $quiz->access_code = $code;
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        return $quiz;
    }

    private function addProtectedQuestion(Quiz $quiz): void
    {
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
    }

    private function publish(Quiz $quiz): void
    {
        app(QuizPublicationService::class)->publish($quiz->owner, $quiz);
    }
}
