<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Services\QuizPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfessorQuizPublicationController extends Controller
{
    public function publish(Request $request, Quiz $quiz, QuizPublicationService $service): RedirectResponse
    {
        $this->authorize('publish', $quiz);
        $revision = $service->publish($request->user(), $quiz);

        $message = config('assessment.routes_enabled')
            ? "Revision {$revision->revision_number} is published. Only verified, assigned Students can start while guarded assessment delivery is enabled."
            : "Revision {$revision->revision_number} is published. Student attempts remain disabled.";

        return back()->with('success', $message);
    }

    public function unpublish(Request $request, Quiz $quiz, QuizPublicationService $service): RedirectResponse
    {
        $this->authorize('unpublish', $quiz);
        $service->unpublish($request->user(), $quiz);

        return back()->with('success', 'The quiz is unpublished. Existing revision and attempt records were preserved.');
    }

    public function createDraft(Request $request, Quiz $quiz, QuizPublicationService $service): RedirectResponse
    {
        $this->authorize('createDraftRevision', $quiz);
        $revision = $service->createDraftFromPublished($request->user(), $quiz);

        return back()->with('success', "Draft revision {$revision->revision_number} is ready for editing.");
    }
}
