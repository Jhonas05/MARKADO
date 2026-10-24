<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateQuizRevisionRequest;
use App\Models\Quiz;
use App\Models\QuizRevision;
use Illuminate\Http\RedirectResponse;

class ProfessorQuizRevisionController extends Controller
{
    public function update(UpdateQuizRevisionRequest $request, Quiz $quiz, QuizRevision $revision): RedirectResponse
    {
        abort_unless($revision->quiz_id === $quiz->id, 404);
        $revision->update($request->validatedForPersistence());

        return back()->with('success', "Draft revision {$revision->revision_number} details saved.");
    }
}
