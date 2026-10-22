<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertQuizQuestionRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProfessorQuizQuestionController extends Controller
{
    public function store(UpsertQuizQuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        DB::transaction(function () use ($request, $quiz): void {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $this->authorize('update', $lockedQuiz);

            $question = $lockedQuiz->questions()->create([
                'type' => $request->validated('type'),
                'prompt' => trim($request->validated('prompt')),
                'position' => ((int) $lockedQuiz->questions()->max('position')) + 1,
            ]);

            foreach ($request->validated('choices') as $index => $choice) {
                $question->choices()->create([
                    'choice_text' => trim($choice['choice_text']),
                    'position' => $index + 1,
                    'is_correct' => $choice['is_correct'],
                ]);
            }
        });

        return back()->with('success', 'Question added to the draft.');
    }

    public function update(
        UpsertQuizQuestionRequest $request,
        Quiz $quiz,
        QuizQuestion $question,
    ): RedirectResponse {
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        DB::transaction(function () use ($request, $question): void {
            $lockedQuestion = QuizQuestion::query()
                ->with('choices')
                ->lockForUpdate()
                ->findOrFail($question->id);

            $lockedQuestion->update([
                'type' => $request->validated('type'),
                'prompt' => trim($request->validated('prompt')),
            ]);

            $existing = $lockedQuestion->choices->keyBy('id');
            foreach ($existing->values() as $index => $choice) {
                $choice->update(['position' => 1000 + $index]);
            }

            $retainedIds = [];
            foreach ($request->validated('choices') as $index => $choiceData) {
                $choice = isset($choiceData['id']) ? $existing->get($choiceData['id']) : null;

                if ($choice) {
                    $choice->update([
                        'choice_text' => trim($choiceData['choice_text']),
                        'position' => $index + 1,
                        'is_correct' => $choiceData['is_correct'],
                    ]);
                    $retainedIds[] = $choice->id;

                    continue;
                }

                $createdChoice = $lockedQuestion->choices()->create([
                    'choice_text' => trim($choiceData['choice_text']),
                    'position' => $index + 1,
                    'is_correct' => $choiceData['is_correct'],
                ]);
                $retainedIds[] = $createdChoice->id;
            }

            $lockedQuestion->choices()->whereNotIn('id', $retainedIds)->delete();
        });

        return back()->with('success', 'Question changes saved.');
    }

    public function destroy(Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        DB::transaction(function () use ($quiz, $question): void {
            Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $question->delete();

            $remaining = $quiz->questions()->lockForUpdate()->get();
            foreach ($remaining as $index => $remainingQuestion) {
                $remainingQuestion->update(['position' => 1000 + $index]);
            }
            foreach ($remaining as $index => $remainingQuestion) {
                $remainingQuestion->update(['position' => $index + 1]);
            }
        });

        return back()->with('success', 'Question removed from the draft.');
    }

    private function ensureQuestionBelongsToQuiz(Quiz $quiz, QuizQuestion $question): void
    {
        abort_unless($question->quiz_id === $quiz->id, 404);
        $this->authorize('update', $quiz);
    }
}
