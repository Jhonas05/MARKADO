<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizStudentAssignmentRequest;
use App\Models\Quiz;
use App\Services\QuizStudentAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfessorQuizStudentAssignmentController extends Controller
{
    public function store(
        StoreQuizStudentAssignmentRequest $request,
        Quiz $quiz,
        QuizStudentAssignmentService $assignments,
    ): RedirectResponse {
        $result = $assignments->assign(
            $request->user(),
            $quiz,
            $request->validated('student_number'),
        );

        $message = match ($result['outcome']) {
            'assigned' => 'Student assigned to this quiz.',
            'reactivated' => 'Student assignment restored.',
            'already_assigned' => 'This Student is already assigned to this quiz.',
        };

        return back()->with('success', $message);
    }

    public function destroy(
        Request $request,
        Quiz $quiz,
        string $assignment,
        QuizStudentAssignmentService $assignments,
    ): RedirectResponse {
        $this->authorize('manageStudentAssignments', $quiz);

        $outcome = $assignments->remove($request->user(), $quiz, $assignment);

        return back()->with('success', $outcome === 'removed'
            ? 'Direct Student assignment removed.'
            : 'This direct Student assignment was already removed.');
    }
}
