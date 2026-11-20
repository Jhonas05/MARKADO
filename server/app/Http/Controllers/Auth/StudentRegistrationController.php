<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Gamification\Support\CharacterEconomyService;
use App\Gamification\Support\GamificationEnvironment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Identity\Contracts\InstitutionalEmailVerificationService;
use App\Identity\Contracts\StudentActivationService;
use App\Identity\Data\InstitutionalEmailVerificationResult;
use App\Identity\Support\StudentRegistrationService;
use App\Identity\Support\StudentSignupService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentRegistrationController extends Controller
{
    public function store(RegisterStudentRequest $request, StudentRegistrationService $registrations, StudentSignupService $signup, InstitutionalEmailVerificationService $verification): RedirectResponse
    {
        $details = $request->safe()->only(['student_number', 'email', 'password']);
        $request->session()->regenerate();
        $request->session()->forget(['registration_user_id', 'registration_candidate']);
        if (config('identity.student_claim_signup_enabled')) {
            $attempt = $signup->start($details['student_number'], $details['email'], $details['password']);
            $student = $attempt['student'] ?? null;
            if ($attempt !== null) {
                $request->session()->put('registration_candidate', $attempt['candidate']);
            }
        } else {
            $student = $registrations->register($details['student_number'], $details['email'], $details['password']);
        }

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

    public function verify(Request $request, InstitutionalEmailVerificationService $verification, StudentActivationService $activation, StudentSignupService $signup): RedirectResponse
    {
        $request->validate(['verification_token' => ['required', 'string', 'size:43', 'regex:/\A[A-Za-z0-9_-]+\z/D']]);
        $student = $this->pendingStudent($request);
        $candidate = $request->session()->get('registration_candidate');
        $token = $request->string('verification_token')->toString();
        $completed = $student !== null && DB::transaction(function () use ($student, $token, $candidate, $verification, $activation, $signup): bool {
            if (is_string($candidate)) {
                if (! $signup->activate($student, $candidate, $token, $verification)) {
                    return false;
                }
            } elseif (! $this->activateRosterStudent($student, $token, $verification, $activation)) {
                return false;
            }

            if (GamificationEnvironment::enabled()) {
                app(CharacterEconomyService::class)->provisionStarterCharacter($student->fresh());
            }

            return true;
        }, 3);

        if (! $completed) {
            throw ValidationException::withMessages(['verification_token' => 'This code is invalid or no longer available. Request another verification email if needed.']);
        }
        $request->session()->forget(['registration_user_id', 'registration_candidate']);
        $request->session()->regenerate();

        return redirect()->route('login')->with('success', 'Email verified. Your Student account is active. You can now sign in.');
    }

    private function activateRosterStudent(
        User $student,
        #[\SensitiveParameter] string $token,
        InstitutionalEmailVerificationService $verification,
        StudentActivationService $activation,
    ): bool {
        if (! $verification->consume($student, $token)->successful) {
            return false;
        }

        $record = $student->schoolStudentRecord;
        $result = $record === null ? null : $activation->activate($student->fresh(), $record->student_number, $student->email);
        if (! $result?->successful) {
            throw ValidationException::withMessages(['verification_token' => 'Verification cannot complete. Check your details with your school.']);
        }

        return true;
    }

    private function pendingStudent(Request $request): ?User
    {
        return User::query()->whereKey($request->session()->get('registration_user_id'))
            ->where('role', AccountRole::Student)->where('account_status', AccountStatus::Pending)->first();
    }
}
