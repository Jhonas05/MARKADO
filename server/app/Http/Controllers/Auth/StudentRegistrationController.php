<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Identity\Contracts\InstitutionalEmailVerificationService;
use App\Identity\Contracts\StudentActivationService;
use App\Identity\Data\InstitutionalEmailVerificationResult;
use App\Identity\Support\StudentRegistrationService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentRegistrationController extends Controller
{
    public function store(RegisterStudentRequest $request, StudentRegistrationService $registrations, InstitutionalEmailVerificationService $verification): RedirectResponse
    {
        $student = $registrations->register(
            $request->validated('student_number'),
            $request->validated('email'),
            $request->validated('password'),
        );
        $request->session()->regenerate();
        $request->session()->forget('registration_user_id');
        if ($student !== null) {
            $request->session()->put('registration_user_id', $student->id);
            $verification->issue($student);
        }

        return redirect()->route('registration.verification')->with('success', InstitutionalEmailVerificationResult::PUBLIC_ACKNOWLEDGEMENT);
    }

    public function show(): Response
    {
        return Inertia::render('Auth/VerifyEmail');
    }

    public function resend(Request $request, InstitutionalEmailVerificationService $verification): RedirectResponse
    {
        $student = $this->pendingStudent($request);
        if ($student !== null) {
            $verification->issue($student);
        }

        return back()->with('success', InstitutionalEmailVerificationResult::PUBLIC_ACKNOWLEDGEMENT);
    }

    public function verify(Request $request, InstitutionalEmailVerificationService $verification, StudentActivationService $activation): RedirectResponse
    {
        $request->validate(['verification_token' => ['required', 'string', 'size:43', 'regex:/\A[A-Za-z0-9_-]+\z/D']]);
        $student = $this->pendingStudent($request);
        $completed = $student !== null && DB::transaction(function () use ($student, $request, $verification, $activation): bool {
            if (! $verification->consume($student, $request->string('verification_token')->toString())->successful) {
                return false;
            }
            $record = $student->schoolStudentRecord;
            $result = $record === null ? null : $activation->activate($student->fresh(), $record->student_number, $student->email);
            if (! $result?->successful) {
                throw ValidationException::withMessages(['verification_token' => 'Verification cannot complete. Check your details with your school.']);
            }

            return true;
        }, 3);

        if (! $completed) {
            throw ValidationException::withMessages(['verification_token' => 'This code is invalid or no longer available. Request another verification email if needed.']);
        }
        $request->session()->forget('registration_user_id');
        $request->session()->regenerate();

        return redirect()->route('login')->with('success', 'Email verified. Your Student account is active. You can now sign in.');
    }

    private function pendingStudent(Request $request): ?User
    {
        return User::query()->whereKey($request->session()->get('registration_user_id'))
            ->where('role', AccountRole::Student)->where('account_status', AccountStatus::Pending)->first();
    }
}
