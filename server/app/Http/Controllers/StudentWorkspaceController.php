<?php

namespace App\Http\Controllers;

use App\Identity\Contracts\StudentIdentitySource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentWorkspaceController extends Controller
{
    public function __construct(private readonly StudentIdentitySource $identitySource)
    {
    }

    public function __invoke(Request $request): Response
    {
        $student = $request->user();
        $studentRecord = $student->schoolStudentRecord;
        $selfReportedNumber = $studentRecord === null
            ? DB::table('student_self_reported_claims')->where('user_id', $student->id)->value('student_number')
            : null;
        $identity = $this->identitySource->evaluate($student);

        return Inertia::render('Student/Workspace', [
            'eligibility' => [
                'hasSchoolRecord' => $studentRecord !== null,
                'isActive' => $studentRecord?->is_active ?? false,
                'studentNumber' => $studentRecord?->student_number ?? $selfReportedNumber,
                'verificationStatus' => $studentRecord?->verification_status->value,
                'verificationLabel' => $studentRecord === null && $selfReportedNumber !== null
                    ? 'Self-reported (unverified)'
                    : ucfirst($studentRecord?->verification_status->value ?? 'unverified'),
                'mailboxVerified' => $student->email_verified_at !== null,
                'identityMatched' => $identity->matched,
            ],
            'quizJoinUrl' => route('student.quizzes.join'),
            'assessmentStartAvailable' => (bool) config('assessment.routes_enabled'),
        ]);
    }
}
