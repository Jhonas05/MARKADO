<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertQuizRevisionQuestionRequest;
use App\Models\Quiz;
use App\Models\QuizRevision;
use App\Models\QuizRevisionQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProfessorQuizRevisionQuestionController extends Controller
{
    public function store(
        UpsertQuizRevisionQuestionRequest $request,
        Quiz $quiz,
        QuizRevision $revision,
    ): RedirectResponse {
        $this->ensureRevisionBelongsToQuiz($quiz, $revision);

        DB::transaction(function () use ($request, $revision): void {
            $lockedRevision = QuizRevision::query()->lockForUpdate()->findOrFail($revision->id);
            $this->authorize('update', $lockedRevision);

            $question = $lockedRevision->questions()->create([
                'type' => $request->validated('type'),
                'prompt' => trim($request->validated('prompt')),
                'position' => ((int) $lockedRevision->questions()->max('position')) + 1,
            ]);
            foreach ($request->validated('choices') as $index => $choice) {
                $question->choices()->create([
                    'choice_text' => trim($choice['choice_text']),
                    'position' => $index + 1,
                    'is_correct' => $choice['is_correct'],
                ]);
            }
        });

        return back()->with('success', 'Question added to the draft revision.');
    }

    public function update(
        UpsertQuizRevisionQuestionRequest $request,
        Quiz $quiz,
        QuizRevision $revision,
        QuizRevisionQuestion $revisionQuestion,
    ): RedirectResponse {
        $this->ensureNestedQuestion($quiz, $revision, $revisionQuestion);

        DB::transaction(function () use ($request, $revisionQuestion): void {
            $lockedQuestion = QuizRevisionQuestion::query()
                ->with('choices')
                ->lockForUpdate()
                ->findOrFail($revisionQuestion->id);
            $lockedQuestion->update([
                'type' => $request->validated('type'),
                'prompt' => trim($request->validated('prompt')),
            ]);

            $existing = $lockedQuestion->choices->keyBy('id');
            foreach ($existing->values() as $index => $choice) {
                $choice->update(['position' => 1000 + $index]);
            }

            $retained = [];
            foreach ($request->validated('choices') as $index => $choiceData) {
                $choice = isset($choiceData['id']) ? $existing->get($choiceData['id']) : null;
                if ($choice) {
                    $choice->update([
                        'choice_text' => trim($choiceData['choice_text']),
                        'position' => $index + 1,
                        'is_correct' => $choiceData['is_correct'],
                    ]);
                } else {
                    $choice = $lockedQuestion->choices()->create([
                        'choice_text' => trim($choiceData['choice_text']),
                        'position' => $index + 1,
                        'is_correct' => $choiceData['is_correct'],
                    ]);
                }
                $retained[] = $choice->id;
            }
            $lockedQuestion->choices()->whereNotIn('id', $retained)->get()->each->delete();
        });

        return back()->with('success', 'Draft revision question saved.');
    }

    public function destroy(
        Quiz $quiz,
        QuizRevision $revision,
        QuizRevisionQuestion $revisionQuestion,
    ): RedirectResponse {
        $this->ensureNestedQuestion($quiz, $revision, $revisionQuestion);

        DB::transaction(function () use ($revision, $revisionQuestion): void {
            QuizRevision::query()->lockForUpdate()->findOrFail($revision->id);
            $revisionQuestion->delete();
            $remaining = $revision->questions()->lockForUpdate()->get();
            foreach ($remaining as $index => $question) {
                $question->update(['position' => 1000 + $index]);
            }
            foreach ($remaining as $index => $question) {
                $question->update(['position' => $index + 1]);
            }
        });

        return back()->with('success', 'Question removed from the draft revision.');
    }

    private function ensureRevisionBelongsToQuiz(Quiz $quiz, QuizRevision $revision): void
    {
        abort_unless($revision->quiz_id === $quiz->id, 404);
        $this->authorize('update', $revision);
    }

    private function ensureNestedQuestion(
        Quiz $quiz,
        QuizRevision $revision,
        QuizRevisionQuestion $question,
    ): void {
        $this->ensureRevisionBelongsToQuiz($quiz, $revision);
        abort_unless($question->quiz_revision_id === $revision->id, 404);
    }
}
