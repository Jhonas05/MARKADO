<?php

namespace App\Policies;

use App\Enums\AccountRole;
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

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    private function owns(User $user, Quiz $quiz): bool
    {
        return $user->role === AccountRole::Instructor
            && $quiz->owner_id === $user->getKey();
    }
}
