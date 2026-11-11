<?php

namespace App\Console\Commands;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Support\QuestionDeadlineAdvancer;
use App\Models\QuizAttempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileExpiredAttempts extends Command
{
    protected $signature = 'assessment:reconcile-expired {--batch= : Maximum attempts to reconcile}';

    protected $description = 'Reconcile persisted assessment deadlines in bounded, repeat-safe batches';

    public function handle(
        AuthoritativeClock $clock,
        QuestionDeadlineAdvancer $questionAdvancer,
    ): int {
        if (! config('assessment.expiration_enabled')) {
            $this->components->info('Assessment expiration processing is disabled by server configuration.');

            return self::SUCCESS;
        }

        $batchSize = (int) ($this->option('batch') ?: config('assessment.expiration_batch_size'));
        $batchSize = max(1, min($batchSize, 500));
        $startedAt = hrtime(true);
        $now = $clock->now();
        $attemptIds = QuizAttempt::query()
            ->where('status', 'in_progress')
            ->where(function ($query) use ($now): void {
                $query->where('effective_deadline_at', '<=', $now)
                    ->orWhereHas('questionStates', fn ($states) => $states
                        ->where('status', 'active')
                        ->whereNotNull('deadline_at')
                        ->where('deadline_at', '<=', $now));
            })
            ->orderBy('effective_deadline_at')
            ->limit($batchSize)
            ->pluck('id');

        $processed = 0;
        foreach ($attemptIds as $attemptId) {
            $attempt = QuizAttempt::query()->find($attemptId);
            if ($attempt === null || $attempt->status !== 'in_progress') {
                continue;
            }

            $questionAdvancer->synchronize($attempt);
            $processed++;
        }

        $this->components->info("Reconciled {$processed} assessment attempt(s).");
        Log::info('Assessment expiration reconciliation completed.', [
            'event' => 'assessment_expiration_reconciliation',
            'candidates' => $attemptIds->count(),
            'processed' => $processed,
            'batch_size' => $batchSize,
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
        ]);

        return self::SUCCESS;
    }
}
