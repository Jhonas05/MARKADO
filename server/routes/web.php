<?php

use App\Http\Controllers\StudentDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', StudentDashboardController::class)->name('student.dashboard');

Route::get('/foundation', StudentDashboardController::class)->name('foundation.demo');

Route::fallback(fn (Request $request) => Inertia::render('ErrorPage', [
    'status' => 404,
])->toResponse($request)->setStatusCode(404))->name('fallback');
