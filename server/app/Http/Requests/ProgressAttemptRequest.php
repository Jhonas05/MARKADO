<?php

namespace App\Http\Requests;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Http\FormRequest;

class ProgressAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');

        return $attempt instanceof QuizAttempt
            && ($this->user()?->can('mutate', $attempt) ?? false);
    }

    public function rules(): array
    {
        return ['expected_version' => ['required', 'integer', 'min:0']];
    }
}
