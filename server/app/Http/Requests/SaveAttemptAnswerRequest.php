<?php

namespace App\Http\Requests;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Http\FormRequest;

class SaveAttemptAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');

        return $attempt instanceof QuizAttempt
            && ($this->user()?->can('mutate', $attempt) ?? false);
    }

    public function rules(): array
    {
        return [
            'question_snapshot_id' => ['required', 'uuid'],
            'selected_choice_id' => ['nullable', 'uuid'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:64', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._:-]+\z/'],
            'expected_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
