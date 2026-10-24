<?php

use App\Http\Middleware\EnsureAccountRole;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureAssessmentEngineEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'account-role' => EnsureAccountRole::class,
            'active-account' => EnsureActiveAccount::class,
            'assessment-enabled' => EnsureAssessmentEngineEnabled::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if (app()->environment(['local', 'testing']) || ! in_array($status, [403, 404, 500, 503], true)) {
                return $response;
            }

            return Inertia::render('ErrorPage', [
                'status' => $status,
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
