<?php

namespace App\Identity\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Contracts\InstitutionalMailboxProof;
use App\Identity\Contracts\RegistrationClaimVerifier;
use App\Identity\Data\RegistrationClaimDecision;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DatabaseRegistrationClaimVerifier implements RegistrationClaimVerifier
{
    public function __construct(
        private readonly InstitutionalEmailPolicy $emailPolicy,
        private readonly InstitutionalMailboxProof $mailboxProof,
    ) {}

    public function verify(
        User $student,
        string $studentNumber,
        string $institutionalEmail,
    ): RegistrationClaimDecision {
        return DB::transaction(function () use ($student, $studentNumber, $institutionalEmail): RegistrationClaimDecision {
            $lockedStudent = User::query()->lockForUpdate()->find($student->getKey());
            if ($lockedStudent === null || $lockedStudent->role !== AccountRole::Student) {
                return RegistrationClaimDecision::denied('student_account_required');
            }

            if ($lockedStudent->account_status === AccountStatus::Disabled) {
                return RegistrationClaimDecision::denied('account_disabled');
            }

            if ($studentNumber === '' || $studentNumber !== trim($studentNumber) || strlen($studentNumber) > 64) {
                return RegistrationClaimDecision::denied('registration_claim_invalid');
            }

            $mailbox = $this->mailboxProof->evaluate($lockedStudent, $institutionalEmail);
            if (! $mailbox->matched) {
                return RegistrationClaimDecision::denied($mailbox->reason);
            }

            $accountEmail = $this->emailPolicy->normalizeAllowedAddress((string) $lockedStudent->email);

            $record = SchoolStudentRecord::query()
                ->where('student_number', $studentNumber)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                return RegistrationClaimDecision::denied('trusted_roster_identity_unavailable');
            }

            if ($record->user_id !== null && $record->user_id !== $lockedStudent->id) {
                return RegistrationClaimDecision::denied('identity_owned_by_another_account');
            }

            $rosterEmail = is_string($record->institutional_email)
                ? $this->emailPolicy->normalizeAllowedAddress($record->institutional_email)
                : null;
            if ($rosterEmail === null || ! hash_equals($rosterEmail, $accountEmail)) {
                return RegistrationClaimDecision::denied('institutional_identity_mismatch');
            }

            if ($record->verification_status === StudentVerificationStatus::Disputed) {
                return RegistrationClaimDecision::denied('trusted_roster_identity_disputed');
            }

            if ($record->verification_status !== StudentVerificationStatus::Verified
                || $record->verified_at === null) {
                return RegistrationClaimDecision::denied('trusted_roster_identity_unverified');
            }

            if (! $record->is_active) {
                return RegistrationClaimDecision::denied('trusted_roster_identity_inactive');
            }

            if ($record->user_id === null && $record->linked_at !== null) {
                return RegistrationClaimDecision::denied('trusted_roster_link_conflict');
            }

            return RegistrationClaimDecision::verified($record->id);
        });
    }
}
