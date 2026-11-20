<?php

namespace App\Http\Requests\Auth;

use App\Identity\Contracts\InstitutionalEmailPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number' => ['bail', 'required', 'string', 'max:64', 'regex:/\A[^\s\x00-\x1F\x7F]+\z/u'],
            'email' => ['bail', 'required', 'string', 'email', 'max:254', function ($attribute, $value, $fail): void {
                if (app(InstitutionalEmailPolicy::class)->normalizeAllowedAddress($value) === null) {
                    $fail('Use your authorized school-issued institutional email address.');
                }
            }],
            'password' => ['bail', 'required', 'string', function ($attribute, $value, $fail): void {
                if (str_contains($value, "\0") || strlen($value) > 72) {
                    $fail('Use a password of at most 72 bytes without null characters.');
                }
            }, Password::min(12)->letters()->mixedCase()->numbers(), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
