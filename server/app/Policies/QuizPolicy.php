<?php

namespace App\Policies;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActive($user) && $user->role === AccountRole::Instructor;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function create(User $user): bool
    {
        return $this->isActive($user) && $user->role === AccountRole::Instructor;
    }

    /**
     * Authorize disclosure of the limited student preflight projection only.
     * Eligibility remains a separate, closed gate until its product rules are approved.
     */
    public function viewPreflight(User $user, Quiz $quiz): bool
    {
        return $this->isActive($user)
            && $user->role === AccountRole::Student
            && $quiz->status === QuizStatus::Published
            && $quiz->current_published_revision_id !== null;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && $quiz->status === QuizStatus::Draft
            && $quiz->current_published_revision_id === null
            && $quiz->active_draft_revision_id === null;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && $quiz->status === QuizStatus::Draft
            && $quiz->current_published_revision_id === null
            && $quiz->active_draft_revision_id === null;
    }

    public function publish(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function unpublish(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && $quiz->current_published_revision_id !== null
            && in_array($quiz->status, [QuizStatus::Published, QuizStatus::Unpublished], true);
    }

    public function createDraftRevision(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && $quiz->current_published_revision_id !== null;
    }

    private function owns(User $user, Quiz $quiz): bool
    {
        return $this->isActive($user)
            && $user->role === AccountRole::Instructor
            && $quiz->owner_id === $user->getKey();
    }

    private function isActive(User $user): bool
    {
        return $user->account_status === AccountStatus::Active;
    }
}
