<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Enums\QuizRevisionStatus;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\QuizPublicationEvent;
use App\Models\QuizRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class QuizPublicationService
{
    public function publish(User $actor, Quiz $quiz): QuizRevision
    {
        return DB::transaction(function () use ($actor, $quiz): QuizRevision {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);

            if ($lockedQuiz->active_draft_revision_id !== null) {
                $revision = QuizRevision::query()
                    ->with('questions.choices')
                    ->lockForUpdate()
                    ->findOrFail($lockedQuiz->active_draft_revision_id);
                $this->assertRevisionBelongsToQuiz($lockedQuiz, $revision);
                $this->validateRevision($revision);

                $revision->status = QuizRevisionStatus::Published;
                $revision->published_by = $actor->id;
                $revision->published_at = now();
                $revision->save();

                $this->selectPublishedRevision($lockedQuiz, $revision);
                $this->record($lockedQuiz, $revision, $actor, 'published');

                return $revision;
            }

            if ($lockedQuiz->current_published_revision_id !== null) {
                $revision = QuizRevision::query()
                    ->with('questions.choices')
                    ->findOrFail($lockedQuiz->current_published_revision_id);
                $this->assertRevisionBelongsToQuiz($lockedQuiz, $revision);

                if ($lockedQuiz->status !== QuizStatus::Published) {
                    $lockedQuiz->status = QuizStatus::Published;
                    $lockedQuiz->save();
                    $this->record($lockedQuiz, $revision, $actor, 'republished');
                }

                return $revision;
            }

            $lockedQuiz->load('questions.choices');
            $this->validateAuthoringQuiz($lockedQuiz);
            $revision = $this->snapshotInitialDraft($lockedQuiz, $actor);
            $this->selectPublishedRevision($lockedQuiz, $revision);
            $this->record($lockedQuiz, $revision, $actor, 'published');

            return $revision;
        });
    }

    public function createDraftFromPublished(User $actor, Quiz $quiz): QuizRevision
    {
        return DB::transaction(function () use ($actor, $quiz): QuizRevision {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);

            if ($lockedQuiz->active_draft_revision_id !== null) {
                return QuizRevision::query()->findOrFail($lockedQuiz->active_draft_revision_id);
            }

            $source = QuizRevision::query()
                ->with('questions.choices')
                ->findOrFail($lockedQuiz->current_published_revision_id);
            $this->assertRevisionBelongsToQuiz($lockedQuiz, $source);

            $revision = new QuizRevision([
                'revision_number' => ((int) $lockedQuiz->revisions()->max('revision_number')) + 1,
                'status' => QuizRevisionStatus::Draft,
                'title' => $source->title,
                'course_code' => $source->course_code,
                'course_name' => $source->course_name,
                'instructions' => $source->instructions,
                'display_timezone' => $source->display_timezone,
                'opens_at' => $source->opens_at,
                'closes_at' => $source->closes_at,
                'whole_quiz_duration_seconds' => $source->whole_quiz_duration_seconds,
                'per_question_duration_seconds' => $source->per_question_duration_seconds,
                'randomize_questions' => $source->randomize_questions,
                'randomize_choices' => $source->randomize_choices,
            ]);
            $revision->quiz()->associate($lockedQuiz);
            $revision->sourceRevision()->associate($source);
            $revision->createdBy()->associate($actor);
            $revision->save();
            $this->copyQuestions($source->questions, $revision);

            $lockedQuiz->active_draft_revision_id = $revision->id;
            $lockedQuiz->save();
            $this->record($lockedQuiz, $revision, $actor, 'draft_created');

            return $revision;
        });
    }

    public function unpublish(User $actor, Quiz $quiz): QuizRevision
    {
        return DB::transaction(function () use ($actor, $quiz): QuizRevision {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $revision = QuizRevision::query()->findOrFail($lockedQuiz->current_published_revision_id);
            $this->assertRevisionBelongsToQuiz($lockedQuiz, $revision);

            if ($lockedQuiz->status === QuizStatus::Published) {
                $lockedQuiz->status = QuizStatus::Unpublished;
                $lockedQuiz->save();
                $this->record($lockedQuiz, $revision, $actor, 'unpublished');
            }

            return $revision;
        });
    }

    private function snapshotInitialDraft(Quiz $quiz, User $actor): QuizRevision
    {
        $revision = new QuizRevision([
            'revision_number' => 1,
            'status' => QuizRevisionStatus::Draft,
            'title' => $quiz->title,
            'course_code' => $quiz->course_code,
            'course_name' => $quiz->course_name,
            'instructions' => $quiz->instructions,
            'display_timezone' => $quiz->display_timezone,
            'opens_at' => $quiz->opens_at,
            'closes_at' => $quiz->closes_at,
            'whole_quiz_duration_seconds' => $quiz->whole_quiz_duration_seconds,
            'per_question_duration_seconds' => $quiz->per_question_duration_seconds,
            'randomize_questions' => $quiz->randomize_questions,
            'randomize_choices' => $quiz->randomize_choices,
        ]);
        $revision->quiz()->associate($quiz);
        $revision->createdBy()->associate($actor);
        $revision->save();

        foreach ($quiz->questions as $question) {
            $revisionQuestion = $revision->questions()->create([
                'source_question_id' => $question->id,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'position' => $question->position,
            ]);
            foreach ($question->choices as $choice) {
                $revisionQuestion->choices()->create([
                    'source_choice_id' => $choice->id,
                    'choice_text' => $choice->choice_text,
                    'position' => $choice->position,
                    'is_correct' => $choice->is_correct,
                ]);
            }
        }

        $revision->load('questions.choices');
        $revision->status = QuizRevisionStatus::Published;
        $revision->published_by = $actor->id;
        $revision->published_at = now();
        $revision->save();

        return $revision;
    }

    private function copyQuestions($questions, QuizRevision $target): void
    {
        foreach ($questions as $question) {
            $copy = $target->questions()->create([
                'source_question_id' => $question->source_question_id,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'position' => $question->position,
            ]);
            foreach ($question->choices as $choice) {
                $copy->choices()->create([
                    'source_choice_id' => $choice->source_choice_id,
                    'choice_text' => $choice->choice_text,
                    'position' => $choice->position,
                    'is_correct' => $choice->is_correct,
                ]);
            }
        }
    }

    private function selectPublishedRevision(Quiz $quiz, QuizRevision $revision): void
    {
        $quiz->forceFill([
            'title' => $revision->title,
            'course_code' => $revision->course_code,
            'course_name' => $revision->course_name,
            'instructions' => $revision->instructions,
            'display_timezone' => $revision->display_timezone,
            'opens_at' => $revision->opens_at,
            'closes_at' => $revision->closes_at,
            'whole_quiz_duration_seconds' => $revision->whole_quiz_duration_seconds,
            'per_question_duration_seconds' => $revision->per_question_duration_seconds,
            'randomize_questions' => $revision->randomize_questions,
            'randomize_choices' => $revision->randomize_choices,
            'status' => QuizStatus::Published,
            'current_published_revision_id' => $revision->id,
            'active_draft_revision_id' => null,
        ])->save();
    }

    private function validateAuthoringQuiz(Quiz $quiz): void
    {
        $this->validateSchedule($quiz);
        $this->validateQuestions($quiz->questions);
    }

    private function validateRevision(QuizRevision $revision): void
    {
        if ($revision->status !== QuizRevisionStatus::Draft) {
            throw ValidationException::withMessages([
                'publication' => 'Only the active draft revision can be published.',
            ]);
        }

        $this->validateSchedule($revision);
        $this->validateQuestions($revision->questions);
    }

    private function validateQuestions($questions): void
    {
        $errors = [];

        if ($questions->isEmpty()) {
            $errors['publication'] = 'Add at least one valid question before publishing.';
        }

        foreach ($questions as $question) {
            $choices = $question->choices;
            $validType = $question->type instanceof QuestionType;
            $correctCount = $choices->where('is_correct', true)->count();

            if (! $validType || $choices->count() < 2 || $choices->count() > 6 || $correctCount !== 1) {
                $errors["questions.{$question->position}"] = "Question {$question->position} is not publishable.";
            }

            if ($question->type === QuestionType::TrueFalse) {
                $labels = $choices->pluck('choice_text')->map(fn ($value) => strtolower(trim($value)))->sort()->values()->all();
                if ($labels !== ['false', 'true']) {
                    $errors["questions.{$question->position}"] = "Question {$question->position} must use the choices True and False.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateSchedule(Quiz|QuizRevision $record): void
    {
        if (($record->opens_at === null) !== ($record->closes_at === null)) {
            throw ValidationException::withMessages([
                'schedule' => 'Opening and closing times must be configured together.',
            ]);
        }

        if ($record->opens_at !== null && $record->opens_at >= $record->closes_at) {
            throw ValidationException::withMessages([
                'schedule' => 'The closing time must be after the opening time.',
            ]);
        }

        foreach ([$record->whole_quiz_duration_seconds, $record->per_question_duration_seconds] as $duration) {
            if ($duration !== null && $duration < 1) {
                throw ValidationException::withMessages([
                    'schedule' => 'Enabled timer durations must be positive.',
                ]);
            }
        }
    }

    private function assertRevisionBelongsToQuiz(Quiz $quiz, QuizRevision $revision): void
    {
        abort_unless($revision->quiz_id === $quiz->id, 404);
    }

    private function record(Quiz $quiz, QuizRevision $revision, User $actor, string $type): void
    {
        $event = new QuizPublicationEvent([
            'event_type' => $type,
            'occurred_at' => now(),
        ]);
        $event->quiz()->associate($quiz);
        $event->revision()->associate($revision);
        $event->actor()->associate($actor);
        $event->save();
    }
}
