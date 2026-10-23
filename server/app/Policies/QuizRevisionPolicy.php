<?php

namespace App\Policies;

use App\Enums\AccountRole;
use App\Enums\QuizRevisionStatus;
use App\Models\QuizRevision;
use App\Models\User;

class QuizRevisionPolicy
{
    public function view(User $user, QuizRevision $revision): bool
    {
        return $this->owns($user, $revision);
    }

    public function update(User $user, QuizRevision $revision): bool
    {
        return $this->owns($user, $revision)
            && $revision->status === QuizRevisionStatus::Draft
            && $revision->quiz?->active_draft_revision_id === $revision->id;
    }

    private function owns(User $user, QuizRevision $revision): bool
    {
        return $user->role === AccountRole::Instructor
            && $revision->quiz?->owner_id === $user->getKey();
    }
}
