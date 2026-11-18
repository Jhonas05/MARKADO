<?php

use App\Assessment\Support\ReconciliationRunObserver;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Stringable;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('assessment:reconcile-expired')
    ->name('assessment-expiration-reconciliation')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping(2)
    ->onSuccess(fn (Stringable $output) => app(ReconciliationRunObserver::class)->recordSuccess($output))
    ->onFailure(fn () => app(ReconciliationRunObserver::class)->recordFailure());
