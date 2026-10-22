<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AuthenticatedWorkspaceController;
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

Route::fallback(fn (Request $request) => Inertia::render('ErrorPage', [
    'status' => 404,
])->toResponse($request)->setStatusCode(404))->name('fallback');
