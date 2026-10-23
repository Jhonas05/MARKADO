<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentWorkspaceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $studentRecord = $request->user()->schoolStudentRecord;

        return Inertia::render('Student/Workspace', [
            'eligibility' => [
                'hasSchoolRecord' => $studentRecord !== null,
                'isActive' => $studentRecord?->is_active ?? false,
                'studentNumber' => $studentRecord?->student_number,
                'verificationStatus' => $studentRecord?->verification_status->value,
                'verificationLabel' => ucfirst($studentRecord?->verification_status->value ?? 'unverified'),
            ],
            'quizJoinUrl' => route('student.quizzes.join'),
        ]);
    }
}
