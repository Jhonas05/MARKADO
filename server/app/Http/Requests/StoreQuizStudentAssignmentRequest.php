<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuizStudentAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz
            && $this->user()?->can('manageStudentAssignments', $quiz) === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'student_number' => ['bail', 'required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.required' => 'Enter the exact institutional student number.',
            'student_number.max' => 'The student number must not exceed 64 characters.',
        ];
    }
}
