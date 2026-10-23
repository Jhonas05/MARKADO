<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use App\Models\QuizRevision;
use App\Models\QuizRevisionQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertQuizRevisionQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $revision = $this->route('revision');

        return $revision instanceof QuizRevision && ($this->user()?->can('update', $revision) ?? false);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string', 'min:3', 'max:5000'],
            'choices' => ['required', 'array', 'min:2', 'max:6'],
            'choices.*' => ['required', 'array'],
            'choices.*.id' => ['nullable', 'uuid'],
            'choices.*.choice_text' => ['required', 'string', 'max:1000'],
            'choices.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $choices = collect(is_array($this->input('choices')) ? $this->input('choices') : [])
                ->filter(fn (mixed $choice): bool => is_array($choice));
            $correctCount = $choices->filter(
                fn (array $choice): bool => filter_var($choice['is_correct'] ?? false, FILTER_VALIDATE_BOOL)
            )->count();

            if ($correctCount !== 1) {
                $validator->errors()->add('choices', 'Select exactly one correct answer.');
            }

            $labels = $choices->pluck('choice_text')->map(fn ($value) => strtolower(trim((string) $value)));
            if ($labels->filter()->duplicates()->isNotEmpty()) {
                $validator->errors()->add('choices', 'Answer choices must be distinct.');
            }

            if ($this->input('type') === QuestionType::TrueFalse->value && $labels->sort()->values()->all() !== ['false', 'true']) {
                $validator->errors()->add('choices', 'True/False questions must contain exactly the choices True and False.');
            }

            $question = $this->route('revisionQuestion');
            if (! $question instanceof QuizRevisionQuestion) {
                return;
            }

            $submittedIds = $choices->pluck('id')->filter()->values();
            if ($submittedIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add('choices', 'A choice identifier may only be submitted once.');
            }
            $ownedIds = $question->choices()->whereKey($submittedIds)->pluck('id');
            if ($submittedIds->diff($ownedIds)->isNotEmpty()) {
                $validator->errors()->add('choices', 'One or more choices do not belong to this question.');
            }
        }];
    }
}
