<?php

namespace App\Identity\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Contracts\StudentIdentitySource;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DatabaseStudentIdentitySource implements StudentIdentitySource
{
    public function __construct(
        private readonly InstitutionalEmailPolicy $emailPolicy,
        private readonly IdentityAccessEventRecorder $events,
    ) {}

    public function evaluate(User $student): StudentIdentityDecision
    {
        return $this->evaluateTransactionally($student);
    }

    public function matchClaim(
        User $student,
        string $studentNumber,
        string $institutionalEmail,
    ): StudentIdentityDecision {
        return $this->evaluateTransactionally($student, $studentNumber, $institutionalEmail);
    }

    private function evaluateTransactionally(
        User $student,
        ?string $claimedStudentNumber = null,
        ?string $claimedEmail = null,
    ): StudentIdentityDecision {
        return DB::transaction(function () use ($student, $claimedStudentNumber, $claimedEmail): StudentIdentityDecision {
            $lockedStudent = User::query()->lockForUpdate()->find($student->getKey());
            if ($lockedStudent === null || $lockedStudent->role !== AccountRole::Student) {
                return $this->deny($student, 'student_account_required');
            }

            $accountEmail = is_string($lockedStudent->email)
                ? $this->emailPolicy->normalizeAllowedAddress($lockedStudent->email)
                : null;
            if ($accountEmail === null) {
                return $this->deny($lockedStudent, 'institutional_email_not_allowed');
            }

            if ($lockedStudent->email_verified_at === null) {
                return $this->deny($lockedStudent, 'institutional_email_unverified');
            }

            $record = $claimedStudentNumber === null
                ? SchoolStudentRecord::query()->where('user_id', $lockedStudent->id)->lockForUpdate()->first()
                : SchoolStudentRecord::query()->where('student_number', $claimedStudentNumber)->lockForUpdate()->first();

            if ($record === null) {
                return $this->deny($lockedStudent, 'trusted_roster_record_missing');
            }

            if ($record->user_id !== $lockedStudent->id) {
                return $this->deny($lockedStudent, 'identity_owned_by_another_account');
            }

            if ($claimedStudentNumber !== null && $record->student_number !== $claimedStudentNumber) {
                return $this->deny($lockedStudent, 'student_number_mismatch');
            }

            $rosterEmail = is_string($record->institutional_email)
                ? $this->emailPolicy->normalizeAllowedAddress($record->institutional_email)
                : null;
            if ($rosterEmail === null || ! hash_equals($rosterEmail, $accountEmail)) {
                return $this->deny($lockedStudent, 'roster_email_mismatch');
            }

            if ($claimedEmail !== null) {
                $normalizedClaim = $this->emailPolicy->normalizeAllowedAddress($claimedEmail);
                if ($normalizedClaim === null || ! hash_equals($normalizedClaim, $accountEmail)) {
                    return $this->deny($lockedStudent, 'claimed_email_mismatch');
                }
            }

            if ($record->verification_status !== StudentVerificationStatus::Verified
                || $record->verified_at === null) {
                return $this->deny($lockedStudent, 'trusted_roster_record_unverified');
            }

            if (! $record->is_active) {
                return $this->deny($lockedStudent, 'trusted_roster_record_inactive');
            }

            if ($lockedStudent->account_status !== AccountStatus::Active) {
                return $this->deny($lockedStudent, 'account_not_active');
            }

            return $this->record($lockedStudent, StudentIdentityDecision::matched());
        });
    }

    private function deny(User $user, string $reason): StudentIdentityDecision
    {
        return $this->record($user, StudentIdentityDecision::denied($reason));
    }

    private function record(User $user, StudentIdentityDecision $decision): StudentIdentityDecision
    {
        $this->events->record($user, $decision->reason);

        return $decision;
    }
}
