<?php

namespace App\Identity\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class StudentRegistrationService
{
    public function __construct(
        private readonly InstitutionalEmailPolicy $emails,
        private readonly TrustedRosterRecordPolicy $roster,
    ) {}

    public function register(string $studentNumber, string $email, #[\SensitiveParameter] string $password): ?User
    {
        if (! RegistrationEnvironment::enabled()) {
            return null;
        }

        // Hash even a rejected claim; never use registration to update an account.
        $passwordHash = Hash::make($password);
        $normalized = $this->emails->normalizeAllowedAddress($email);
        if ($normalized === null || $studentNumber !== trim($studentNumber)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($studentNumber, $normalized, $passwordHash): ?User {
                $record = SchoolStudentRecord::query()->where('student_number', $studentNumber)->lockForUpdate()->first();
                if ($record === null || $record->user_id !== null || $record->linked_at !== null
                    || ! $record->is_active || $record->verification_status !== StudentVerificationStatus::Verified
                    || $record->verified_at === null
                    || ! $this->roster->accepts($record)
                    || $this->emails->normalizeAllowedAddress((string) $record->institutional_email) !== $normalized) {
                    return null;
                }

                // Conservatively reject case-ambiguous existing email ownership.
                // The accepted mailbox's local part and official number remain exact.
                if (DB::getDriverName() === 'pgsql') {
                    DB::select('select pg_advisory_xact_lock(hashtextextended(?, 0))', [hash_hmac('sha256', 'registration-email:'.strtolower($normalized), (string) config('app.key'))]);
                }
                if (SchoolStudentRecord::query()->whereRaw('lower(institutional_email) = ?', [strtolower($normalized)])->count() !== 1
                    || User::query()->whereRaw('lower(email) = ?', [strtolower($normalized)])->exists()) {
                    return null;
                }

                $student = User::query()->create([
                    'name' => 'Student',
                    'email' => $normalized,
                    'password' => $passwordHash,
                    'role' => AccountRole::Student,
                    'account_status' => AccountStatus::Pending,
                ]);
                // Reserve the existing identity, without approving or changing its roster evidence.
                // Activation alone sets linked_at after independent mailbox proof.
                $record->user()->associate($student);
                $record->save();
                app(IdentityAccessEventRecorder::class)->record($student, 'student_registration_pending');

                return $student;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
