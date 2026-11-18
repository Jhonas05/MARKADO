<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUnverifiedStudentRequest;
use App\Identity\Support\UnverifiedStudentRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UnverifiedStudentRegistrationController extends Controller
{
    public function store(
        RegisterUnverifiedStudentRequest $request,
        UnverifiedStudentRegistrationService $registrations,
    ): RedirectResponse {
        $student = $registrations->register(
            $request->validated('student_number'),
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($student === null) {
            throw ValidationException::withMessages([
                'email' => 'This signup could not be completed. If you already have an account, please sign in.',
            ]);
        }

        Auth::guard('web')->login($student);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard')->with(
            'success',
            'Your Student account is ready. Your school identity is not verified; restricted quizzes still require eligibility checks.',
        );
    }
}
