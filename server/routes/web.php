<?php

use App\Http\Controllers\AdministratorDashboardController;
use App\Http\Controllers\Auth\AccountAccessController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AuthenticatedWorkspaceController;
use App\Http\Controllers\ProfessorQuizController;
use App\Http\Controllers\ProfessorQuizPublicationController;
use App\Http\Controllers\ProfessorQuizQuestionController;
use App\Http\Controllers\ProfessorQuizResultController;
use App\Http\Controllers\ProfessorQuizRevisionController;
use App\Http\Controllers\ProfessorQuizRevisionQuestionController;
use App\Http\Controllers\StudentAssessmentController;
use App\Http\Controllers\StudentCharacterController;
use App\Http\Controllers\StudentQuizJoinController;
use App\Http\Controllers\StudentWorkspaceController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', WelcomeController::class)->name('home');

Route::get('/account/pending', [AccountAccessController::class, 'pending'])->name('account.pending');
Route::get('/account/disabled', [AccountAccessController::class, 'disabled'])->name('account.disabled');
Route::get('/session-expired', [AccountAccessController::class, 'sessionExpired'])->name('session.expired');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(ValidateCsrfToken::class)
        ->name('login.store');
    Route::get('/register', [AccountAccessController::class, 'register'])->name('register');
    Route::get('/forgot-password', [AccountAccessController::class, 'forgotPassword'])->name('password.request');
    Route::get('/reset-password', [AccountAccessController::class, 'resetPassword'])->name('password.reset.unavailable');
});

Route::middleware('auth')->group(function () {
    Route::get('/workspace', AuthenticatedWorkspaceController::class)
        ->middleware('active-account')
        ->name('workspace');
    Route::delete('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'active-account', 'account-role:student'])
    ->get('/student/dashboard', StudentWorkspaceController::class)
    ->name('student.dashboard');

Route::middleware(['auth', 'active-account', 'account-role:student', 'gamification-enabled'])
    ->prefix('student/characters')
    ->name('student.characters.')
    ->group(function () {
        Route::get('collection', [StudentCharacterController::class, 'collection'])->name('collection');
        Route::get('shop', [StudentCharacterController::class, 'shop'])->name('shop');
        Route::post('{character}/purchase', [StudentCharacterController::class, 'purchase'])->name('purchase');
        Route::post('{character}/equip', [StudentCharacterController::class, 'equip'])->name('equip');
    });

Route::middleware(['auth', 'active-account', 'account-role:academic_administrator'])
    ->get('/admin/dashboard', AdministratorDashboardController::class)
    ->name('administrator.dashboard');

Route::middleware(['auth', 'active-account', 'account-role:student', 'throttle:quiz-join'])
    ->prefix('student/quizzes')
    ->name('student.quizzes.')
    ->group(function () {
        Route::get('join', [StudentQuizJoinController::class, 'index'])->name('join');
        Route::post('preflight', [StudentQuizJoinController::class, 'store'])->name('preflight.store');
        Route::get('join/{code}', [StudentQuizJoinController::class, 'show'])->name('preflight.show');
        Route::post('{quiz}/attempts', [StudentAssessmentController::class, 'store'])
            ->middleware('assessment-enabled')
            ->name('attempts.store');
    });

Route::middleware(['auth', 'active-account', 'account-role:student', 'assessment-enabled'])
    ->prefix('student/attempts')
    ->name('student.attempts.')
    ->group(function () {
        Route::get('{attempt}', [StudentAssessmentController::class, 'show'])->name('show');
        Route::post('{attempt}/answers', [StudentAssessmentController::class, 'save'])->name('answers.store');
        Route::post('{attempt}/advance', [StudentAssessmentController::class, 'advance'])->name('advance');
        Route::post('{attempt}/submit', [StudentAssessmentController::class, 'submit'])->name('submit');
    });

Route::middleware(['auth', 'active-account', 'account-role:instructor'])
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
        Route::post('quizzes/{quiz}/publish', [ProfessorQuizPublicationController::class, 'publish'])
            ->name('quizzes.publish');
        Route::post('quizzes/{quiz}/unpublish', [ProfessorQuizPublicationController::class, 'unpublish'])
            ->name('quizzes.unpublish');
        Route::post('quizzes/{quiz}/draft-revisions', [ProfessorQuizPublicationController::class, 'createDraft'])
            ->name('quizzes.revisions.store');
        Route::put('quizzes/{quiz}/revisions/{revision}', [ProfessorQuizRevisionController::class, 'update'])
            ->name('quizzes.revisions.update');
        Route::post('quizzes/{quiz}/revisions/{revision}/questions', [ProfessorQuizRevisionQuestionController::class, 'store'])
            ->name('quizzes.revisions.questions.store');
        Route::put('quizzes/{quiz}/revisions/{revision}/questions/{revisionQuestion}', [ProfessorQuizRevisionQuestionController::class, 'update'])
            ->name('quizzes.revisions.questions.update');
        Route::delete('quizzes/{quiz}/revisions/{revision}/questions/{revisionQuestion}', [ProfessorQuizRevisionQuestionController::class, 'destroy'])
            ->name('quizzes.revisions.questions.destroy');
        Route::get('quizzes/{quiz}/results', [ProfessorQuizResultController::class, 'index'])
            ->middleware('assessment-enabled')
            ->name('quizzes.results.index');
        Route::post('quizzes/{quiz}/attempts/{attempt}/release', [ProfessorQuizResultController::class, 'release'])
            ->middleware('assessment-enabled')
            ->name('quizzes.results.release');
    });

Route::fallback(fn (Request $request) => Inertia::render('ErrorPage', [
    'status' => 404,
])->toResponse($request)->setStatusCode(404))->name('fallback');
