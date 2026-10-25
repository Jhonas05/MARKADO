<?php

namespace App\Identity\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Identity\Contracts\RegistrationClaimVerifier;
use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Contracts\StudentActivationService;
use App\Identity\Data\StudentActivationResult;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DatabaseStudentActivationService implements StudentActivationService
{
    public function __construct(
        private readonly RegistrationClaimVerifier $claims,
        private readonly StudentActivationSecurityGate $securityGate,
        private readonly IdentityAccessEventRecorder $events,
    ) {}

    public function activate(
        User $student,
        string $studentNumber,
        string $institutionalEmail,
    ): StudentActivationResult {
        if (! config('identity.student_self_registration_enabled')
            || ! config('identity.automatic_student_activation_enabled')) {
            return StudentActivationResult::denied('student_activation_disabled');
        }

        return DB::transaction(function () use ($student, $studentNumber, $institutionalEmail): StudentActivationResult {
            $lockedStudent = User::query()->lockForUpdate()->find($student->getKey());
            if ($lockedStudent === null) {
                return StudentActivationResult::denied('student_account_required');
            }

            if ($lockedStudent->role !== AccountRole::Student) {
                return $this->deny($lockedStudent, 'student_account_required');
            }

            if ($lockedStudent->account_status === AccountStatus::Disabled) {
                return $this->deny($lockedStudent, 'account_disabled');
            }

            $claim = $this->claims->verify($lockedStudent, $studentNumber, $institutionalEmail);
            if (! $claim->verified || $claim->rosterRecordId === null) {
                return $this->deny($lockedStudent, $claim->reason);
            }

            $security = $this->securityGate->evaluate($lockedStudent, $claim->rosterRecordId);
            if (! $security->matched) {
                return $this->deny($lockedStudent, $security->reason);
            }

            $record = SchoolStudentRecord::query()->lockForUpdate()->find($claim->rosterRecordId);
            if ($record === null) {
                return $this->deny($lockedStudent, 'trusted_roster_identity_unavailable');
            }

            if ($record->user_id !== null && $record->user_id !== $lockedStudent->id) {
                return $this->deny($lockedStudent, 'identity_owned_by_another_account');
            }

            if ($lockedStudent->account_status === AccountStatus::Active) {
                if ($record->user_id !== $lockedStudent->id) {
                    return $this->deny($lockedStudent, 'active_account_identity_conflict');
                }

                return StudentActivationResult::alreadyActive();
            }

            if ($lockedStudent->account_status !== AccountStatus::Pending) {
                return $this->deny($lockedStudent, 'account_state_not_activatable');
            }

            $record->user()->associate($lockedStudent);
            $record->forceFill(['linked_at' => now()])->save();
            $lockedStudent->forceFill(['account_status' => AccountStatus::Active])->save();
            $this->events->record($lockedStudent, 'student_activated_automatically');

            return StudentActivationResult::activated();
        });
    }

    private function deny(User $student, string $reason): StudentActivationResult
    {
        if ($student->exists) {
            $this->events->record($student, $reason);
        }

        return StudentActivationResult::denied($reason);
    }
}
