<?php

namespace App\Assessment\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

final class ReconciliationRunObserver
{
    public function recordSuccess(Stringable $output): void
    {
        $text = (string) $output;

        if (preg_match('/Reconciled (\d+) assessment attempt\(s\)\./', $text, $match) === 1) {
            Log::info('Assessment expiration worker completed.', [
                'event' => 'assessment_expiration_worker_completed',
                'processed' => (int) $match[1],
            ]);

            return;
        }

        if (str_contains($text, 'Assessment expiration processing is disabled by server configuration.')) {
            Log::info('Assessment expiration worker is disabled.', [
                'event' => 'assessment_expiration_worker_disabled',
            ]);

            return;
        }

        Log::warning('Assessment expiration worker returned an unrecognized result.', [
            'event' => 'assessment_expiration_worker_unrecognized',
        ]);
    }

    public function recordFailure(): void
    {
        Log::error('Assessment expiration worker failed.', [
            'event' => 'assessment_expiration_worker_failed',
        ]);
    }
}
