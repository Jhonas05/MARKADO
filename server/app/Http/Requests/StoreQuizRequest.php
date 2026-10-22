<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Quiz::class) ?? false;
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
