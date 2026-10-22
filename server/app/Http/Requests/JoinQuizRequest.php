<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinQuizRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/\A[A-HJ-NP-Z2-9]{8}\z/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Enter the quiz code supplied by your instructor.',
            'code.regex' => 'Enter a valid 8-character quiz code.',
        ];
    }
}
