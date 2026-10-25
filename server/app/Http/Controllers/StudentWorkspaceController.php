<?php

namespace App\Http\Controllers;

use App\Identity\Contracts\StudentIdentitySource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentWorkspaceController extends Controller
{
    public function __construct(private readonly StudentIdentitySource $identitySource) {}

    public function __invoke(Request $request): Response
    {
        $student = $request->user();
        $studentRecord = $student->schoolStudentRecord;
        $identity = $this->identitySource->evaluate($student);

        return Inertia::render('Student/Workspace', [
            'eligibility' => [
                'hasSchoolRecord' => $studentRecord !== null,
                'isActive' => $studentRecord?->is_active ?? false,
                'studentNumber' => $studentRecord?->student_number,
                'verificationStatus' => $studentRecord?->verification_status->value,
                'verificationLabel' => ucfirst($studentRecord?->verification_status->value ?? 'unverified'),
                'mailboxVerified' => $student->email_verified_at !== null,
                'identityMatched' => $identity->matched,
            ],
            'quizJoinUrl' => route('student.quizzes.join'),
            'assessmentStartAvailable' => (bool) config('assessment.routes_enabled'),
        ]);
    }
}
