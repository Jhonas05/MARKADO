<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Quiz;
use App\Support\QuizAccessCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfessorQuizController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Quiz::class);

        $quizzes = Quiz::query()
            ->whereBelongsTo($request->user(), 'owner')
            ->withCount(['questions', 'attempts'])
            ->latest('updated_at')
            ->get()
            ->map(fn (Quiz $quiz): array => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'courseCode' => $quiz->course_code,
                'courseName' => $quiz->course_name,
                'status' => $quiz->status->value,
                'statusLabel' => $quiz->status->label(),
                'questionCount' => $quiz->questions_count,
                'attemptCount' => $quiz->attempts_count,
                'updatedAt' => $quiz->updated_at?->timezone('Asia/Manila')->toIso8601String(),
            ]);

        return Inertia::render('Professor/Quizzes/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Quiz::class);

        return Inertia::render('Professor/Quizzes/Create', [
            'displayTimezone' => 'Asia/Manila',
        ]);
    }

    public function store(StoreQuizRequest $request): RedirectResponse
    {
        $quiz = DB::transaction(function () use ($request): Quiz {
            $quiz = new Quiz($request->validated());
            $quiz->owner()->associate($request->user());
            $quiz->status = QuizStatus::Draft;
            $quiz->access_code = QuizAccessCode::generate();
            $quiz->display_timezone = 'Asia/Manila';
            $quiz->save();

            return $quiz;
        });

        return to_route('professor.quizzes.edit', $quiz)
            ->with('success', 'Draft quiz created. Add its first question when you are ready.');
    }

    public function edit(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $quiz->load('questions.choices');

        return Inertia::render('Professor/Quizzes/Edit', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'courseCode' => $quiz->course_code,
                'courseName' => $quiz->course_name,
                'instructions' => $quiz->instructions,
                'status' => $quiz->status->value,
                'statusLabel' => $quiz->status->label(),
                'displayTimezone' => $quiz->display_timezone,
                'access' => [
                    'code' => $quiz->access_code,
                    'path' => "/quiz/{$quiz->access_code}",
                    'enabled' => false,
                ],
                'questions' => $quiz->questions->map(fn ($question): array => [
                    'id' => $question->id,
                    'type' => $question->type->value,
                    'typeLabel' => $question->type->label(),
                    'prompt' => $question->prompt,
                    'position' => $question->position,
                    'choices' => $question->choices->map(fn ($choice): array => [
                        'id' => $choice->id,
                        'choiceText' => $choice->choice_text,
                        'position' => $choice->position,
                        'isCorrect' => $choice->is_correct,
                    ])->values(),
                ])->values(),
            ],
            'questionTypes' => collect(QuestionType::cases())->map(fn (QuestionType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $quiz->update($request->validated());

        return back()->with('success', 'Quiz details saved.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        abort_if($quiz->attempts()->exists(), 409, 'Quizzes with attempts cannot be deleted.');

        $quiz->delete();

        return to_route('professor.quizzes.index')->with('success', 'Draft quiz deleted.');
    }
}
