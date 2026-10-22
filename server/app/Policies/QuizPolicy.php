<?php

namespace App\Policies;

use App\Enums\AccountRole;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === AccountRole::Instructor;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function create(User $user): bool
    {
        return $user->role === AccountRole::Instructor;
    }

    /**
     * Authorize disclosure of the limited student preflight projection only.
     * Eligibility remains a separate, closed gate until its product rules are approved.
     */
    public function viewPreflight(User $user, Quiz $quiz): bool
    {
        return $user->role === AccountRole::Student
            && $quiz->status === QuizStatus::Published;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && $quiz->status === QuizStatus::Draft;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && $quiz->status === QuizStatus::Draft;
    }

    private function owns(User $user, Quiz $quiz): bool
    {
        return $user->role === AccountRole::Instructor
            && $quiz->owner_id === $user->getKey();
    }
}
