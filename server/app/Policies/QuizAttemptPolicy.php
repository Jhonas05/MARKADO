<?php

namespace App\Policies;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): bool
    {
        if ($user->account_status !== AccountStatus::Active) {
            return false;
        }

        if ($user->role === AccountRole::Student) {
            return $attempt->student_id === $user->id;
        }

        return $user->role === AccountRole::Instructor
            && $attempt->quiz()->where('owner_id', $user->id)->exists();
    }

    public function mutate(User $user, QuizAttempt $attempt): bool
    {
        return $user->role === AccountRole::Student
            && $user->account_status === AccountStatus::Active
            && $attempt->student_id === $user->id;
    }

    public function release(User $user, QuizAttempt $attempt): bool
    {
        return $user->role === AccountRole::Instructor
            && $user->account_status === AccountStatus::Active
            && $attempt->quiz()->where('owner_id', $user->id)->exists();
    }
}
