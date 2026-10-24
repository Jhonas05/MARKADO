<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Quiz;
use App\Models\QuizRevision;
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
            ->with([
                'currentPublishedRevision' => fn ($query) => $query->withCount('questions'),
                'activeDraftRevision' => fn ($query) => $query->withCount('questions'),
            ])
            ->latest('updated_at')
            ->get()
            ->map(fn (Quiz $quiz): array => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'courseCode' => $quiz->course_code,
                'courseName' => $quiz->course_name,
                'status' => $quiz->status->value,
                'statusLabel' => $quiz->status->label(),
                'questionCount' => $quiz->activeDraftRevision?->questions_count
                    ?? $quiz->currentPublishedRevision?->questions_count
                    ?? $quiz->questions_count,
                'attemptCount' => $quiz->attempts_count,
                'hasDraftRevision' => $quiz->active_draft_revision_id !== null,
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

        $quiz->load([
            'questions.choices',
            'revisions',
            'currentPublishedRevision.questions.choices',
            'activeDraftRevision.questions.choices',
            'sectionAssignments.section',
        ])->loadCount([
            'studentAssignments as active_student_assignments_count' => fn ($query) => $query->where('is_active', true),
        ]);

        $activeRevision = $quiz->activeDraftRevision;
        $publishedRevision = $quiz->currentPublishedRevision;
        $authoringRecord = $activeRevision ?? $publishedRevision ?? $quiz;
        $authoringMode = $activeRevision
            ? 'revision'
            : ($publishedRevision ? 'published_locked' : 'initial_draft');
        $questions = $authoringRecord->questions;
        $revisionId = $authoringRecord instanceof QuizRevision ? $authoringRecord->id : null;
        $questionBase = $revisionId
            ? "/professor/quizzes/{$quiz->id}/revisions/{$revisionId}/questions"
            : "/professor/quizzes/{$quiz->id}/questions";

        return Inertia::render('Professor/Quizzes/Edit', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $authoringRecord->title,
                'courseCode' => $authoringRecord->course_code,
                'courseName' => $authoringRecord->course_name,
                'instructions' => $authoringRecord->instructions,
                'status' => $quiz->status->value,
                'statusLabel' => $quiz->status->label(),
                'displayTimezone' => $authoringRecord->display_timezone,
                'schedule' => [
                    'opensAt' => $authoringRecord->opens_at?->timezone('Asia/Manila')->format('Y-m-d\TH:i'),
                    'closesAt' => $authoringRecord->closes_at?->timezone('Asia/Manila')->format('Y-m-d\TH:i'),
                    'wholeQuizDurationSeconds' => $authoringRecord->whole_quiz_duration_seconds,
                    'perQuestionDurationSeconds' => $authoringRecord->per_question_duration_seconds,
                    'randomizeQuestions' => (bool) $authoringRecord->randomize_questions,
                    'randomizeChoices' => (bool) $authoringRecord->randomize_choices,
                ],
                'resultsPath' => "/professor/quizzes/{$quiz->id}/results",
                'access' => [
                    'code' => $quiz->access_code,
                    'path' => "/student/quizzes/join/{$quiz->access_code}",
                    'enabled' => $quiz->status === QuizStatus::Published,
                ],
                'authoring' => [
                    'mode' => $authoringMode,
                    'editable' => $authoringMode !== 'published_locked',
                    'revisionId' => $revisionId,
                    'revisionNumber' => $authoringRecord instanceof QuizRevision ? $authoringRecord->revision_number : null,
                    'detailsPath' => $revisionId
                        ? "/professor/quizzes/{$quiz->id}/revisions/{$revisionId}"
                        : "/professor/quizzes/{$quiz->id}",
                    'questionsPath' => $questionBase,
                ],
                'publication' => [
                    'canPublish' => $authoringMode !== 'published_locked'
                        || $quiz->status === QuizStatus::Unpublished,
                    'canUnpublish' => $quiz->status === QuizStatus::Published,
                    'canCreateDraft' => $publishedRevision !== null && $activeRevision === null,
                    'currentRevisionNumber' => $publishedRevision?->revision_number,
                    'hasUnpublishedChanges' => $activeRevision !== null,
                    'revisions' => $quiz->revisions->map(fn (QuizRevision $revision): array => [
                        'id' => $revision->id,
                        'number' => $revision->revision_number,
                        'status' => $revision->status->value,
                        'publishedAt' => $revision->published_at?->timezone('Asia/Manila')->toIso8601String(),
                    ])->values(),
                ],
                'assignments' => [
                    'directStudentCount' => $quiz->active_student_assignments_count,
                    'sections' => $quiz->sectionAssignments
                        ->where('is_active', true)
                        ->filter(fn ($assignment) => $assignment->section?->is_active)
                        ->map(fn ($assignment): array => [
                            'code' => $assignment->section->code,
                            'name' => $assignment->section->name,
                        ])->values(),
                    'managementEnabled' => false,
                ],
                'questions' => $questions->map(fn ($question): array => [
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
        $quiz->update($request->validatedForPersistence());

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
