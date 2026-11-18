<?php

namespace App\Identity\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Contracts\InstitutionalEmailVerificationService;
use App\Models\StudentSignupClaim;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class StudentSignupService
{
    public function __construct(
        private readonly InstitutionalEmailPolicy $emails,
        private readonly IdentityAccessEventRecorder $events,
    ) {}

    public function start(string $number, string $email, #[\SensitiveParameter] string $password): ?array
    {
        if (! RegistrationEnvironment::claimSignupEnabled()) {
            return null;
        }

        // Use one canonical mailbox for account ownership, rate limiting and delivery.
        $mailbox = $this->emails->normalizeAllowedAddress(strtolower($email));
        if ($mailbox === null) {
            return null;
        }

        $passwordHash = Hash::make($password);

        try {
            $student = DB::transaction(function () use ($number, $mailbox): ?User {
                StudentRegistrationService::lockMailbox($mailbox);
                $matches = User::query()->whereRaw('lower(email) = ?', [$mailbox])->limit(2)->lockForUpdate()->get();
                $student = $matches->first();

                if ($student === null) {
                    $student = User::query()->create([
                        'name' => 'Student',
                        'email' => $mailbox,
                        'password' => Hash::make(Str::random(48)),
                        'role' => AccountRole::Student,
                        'account_status' => AccountStatus::Pending,
                    ]);
                    StudentSignupClaim::query()->create(['user_id' => $student->id, 'student_number' => $number]);
                    $this->events->record($student, 'student_signup_pending');

                    return $student;
                }

                if ($matches->count() !== 1 || $student->email !== $mailbox || ! $this->isPendingSignup($student)
                    || ! StudentSignupClaim::query()->whereKey($student->id)->exists()) {
                    return null;
                }

                return $student;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        if ($student === null) {
            return null;
        }

        // Keep this attempt's credentials separate from other sessions and the pending account.
        return [
            'student' => $student,
            'candidate' => Crypt::encryptString(json_encode([
                'user' => $student->id,
                'mailbox' => $mailbox,
                'number' => $number,
                'password_hash' => $passwordHash,
            ], JSON_THROW_ON_ERROR)),
        ];
    }

    public function activate(
        User $student,
        #[\SensitiveParameter] string $encryptedCandidate,
        #[\SensitiveParameter] string $token,
        InstitutionalEmailVerificationService $verification,
    ): bool {
        if (! RegistrationEnvironment::claimSignupEnabled()) {
            return false;
        }

        try {
            $candidate = json_decode(Crypt::decryptString($encryptedCandidate), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return false;
        }

        return DB::transaction(function () use ($student, $candidate, $token, $verification): bool {
            $locked = User::query()->lockForUpdate()->find($student->id);
            if ($locked === null || $candidate['user'] !== $locked->id || $candidate['mailbox'] !== $locked->email
                || ! $this->isPendingSignup($locked)) {
                return false;
            }

            $claim = StudentSignupClaim::query()->lockForUpdate()->find($locked->id);
            if ($claim === null || ! $verification->consume($locked, $token)->successful) {
                return false;
            }

            $claim->update(['student_number' => $candidate['number']]);
            $locked->forceFill([
                'password' => $candidate['password_hash'],
                'account_status' => AccountStatus::Active,
            ])->save();
            $this->events->record($locked, 'student_signup_activated');

            return true;
        }, 3);
    }

    private function isPendingSignup(User $student): bool
    {
        return $student->role === AccountRole::Student
            && $student->account_status === AccountStatus::Pending
            && $student->email_verified_at === null;
    }
}
