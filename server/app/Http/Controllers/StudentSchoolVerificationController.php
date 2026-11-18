<?php

namespace App\Http\Controllers;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Contracts\InstitutionalEmailVerificationService;
use App\Identity\Contracts\RegistrationClaimVerifier;
use App\Identity\Support\IdentityAccessEventRecorder;
use App\Identity\Support\LocalRegistrationEnvironment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class StudentSchoolVerificationController extends Controller
{
    public function send(Request $request, InstitutionalEmailVerificationService $verification): RedirectResponse
    {
        abort_unless(LocalRegistrationEnvironment::academicVerificationEnabled(), 404);
        $student = $request->user();
        $number = $this->claim($student);
        $record = SchoolStudentRecord::query()->where('student_number', $number)
            ->whereNull('user_id')->whereNull('linked_at')->where('is_active', true)
            ->where('verification_status', StudentVerificationStatus::Verified)->whereNotNull('verified_at')->first();
        $policy = app(InstitutionalEmailPolicy::class);
        $email = $policy->normalizeAllowedAddress($student->email);
        if ($number !== null && $record !== null && $email !== null
            && $policy->normalizeAllowedAddress((string) $record->institutional_email) === $email) {
            $verification->issue($student, $request->ip());
        }

        return redirect()->route('student.dashboard')->with('success', 'If your school record matches, a code has been sent to your institutional mailbox.');
    }

    public function confirm(Request $request, InstitutionalEmailVerificationService $verification, RegistrationClaimVerifier $claims): RedirectResponse
    {
        abort_unless(LocalRegistrationEnvironment::academicVerificationEnabled(), 404);
        $fields = $request->validate([
            'verification_token' => ['required', 'string', 'size:43', 'regex:/\A[A-Za-z0-9_-]+\z/D'],
            'password' => ['required', 'string', 'max:72', Password::min(12)->letters()->mixedCase()->numbers(), 'confirmed'],
        ]);

        DB::transaction(function () use ($request, $fields, $verification, $claims): void {
            $student = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $number = $this->claim($student);
            if ($student->role !== AccountRole::Student || $student->account_status !== AccountStatus::Active
                || $number === null || $student->schoolStudentRecord()->exists()
                || ! $verification->consume($student, $fields['verification_token'])->successful) {
                throw $this->invalidProof();
            }

            $decision = $claims->verify($student->fresh(), $number, $student->email);
            if (! $decision->verified) {
                throw $this->invalidProof();
            }
            if (Hash::check($fields['password'], $student->password)) {
                throw ValidationException::withMessages(['password' => 'Choose a different password to secure your verified account.']);
            }

            $record = SchoolStudentRecord::query()->lockForUpdate()->findOrFail($decision->rosterRecordId);
            $record->user()->associate($student);
            $record->forceFill(['linked_at' => now()])->save();
            $student->forceFill(['password' => Hash::make($fields['password']), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $student->id)->delete();
            app(IdentityAccessEventRecorder::class)->record($student, 'local_school_identity_linked');
        }, 3);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'School identity verified. Sign in with your new password. Your professor can now assign a quiz.');
    }

    private function claim(User $student): ?string
    {
        return DB::table('student_self_reported_claims')->where('user_id', $student->id)->value('student_number');
    }

    private function invalidProof(): ValidationException
    {
        return ValidationException::withMessages(['verification_token' => 'Verification could not be completed. Check your code or contact your school.']);
    }
}
