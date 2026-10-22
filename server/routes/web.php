<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AuthenticatedWorkspaceController;
use App\Http\Controllers\ProfessorQuizController;
use App\Http\Controllers\ProfessorQuizQuestionController;
use App\Http\Controllers\StudentDashboardController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', StudentDashboardController::class)->name('student.dashboard');

Route::get('/foundation', StudentDashboardController::class)->name('foundation.demo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(ValidateCsrfToken::class)
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/workspace', AuthenticatedWorkspaceController::class)->name('workspace');
    Route::delete('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'account-role:instructor'])
    ->prefix('professor')
    ->name('professor.')
    ->group(function () {
        Route::resource('quizzes', ProfessorQuizController::class)->except('show');
        Route::post('quizzes/{quiz}/questions', [ProfessorQuizQuestionController::class, 'store'])
            ->name('quizzes.questions.store');
        Route::put('quizzes/{quiz}/questions/{question}', [ProfessorQuizQuestionController::class, 'update'])
            ->name('quizzes.questions.update');
        Route::delete('quizzes/{quiz}/questions/{question}', [ProfessorQuizQuestionController::class, 'destroy'])
            ->name('quizzes.questions.destroy');
    });

Route::fallback(fn (Request $request) => Inertia::render('ErrorPage', [
    'status' => 404,
])->toResponse($request)->setStatusCode(404))->name('fallback');
