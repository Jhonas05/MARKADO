<?php

namespace App\Policies;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Models\SchoolStudentRecord;
use App\Models\User;

class SchoolStudentRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, SchoolStudentRecord $record): bool
    {
        return $user->role === AccountRole::Student
            && $user->account_status === AccountStatus::Active
            && $record->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SchoolStudentRecord $record): bool
    {
        return false;
    }

    public function delete(User $user, SchoolStudentRecord $record): bool
    {
        return false;
    }
}
