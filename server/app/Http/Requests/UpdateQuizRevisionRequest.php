<?php

namespace App\Http\Requests;

use App\Models\QuizRevision;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $revision = $this->route('revision');

        return $revision instanceof QuizRevision && ($this->user()?->can('update', $revision) ?? false);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'course_code' => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9 ._\/-]*$/'],
            'course_name' => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
