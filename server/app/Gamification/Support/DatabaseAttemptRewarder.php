<?php

namespace App\Gamification\Support;

use App\Enums\AccountRole;
use App\Gamification\Contracts\AttemptRewarder;
use App\Models\AttemptCoinReward;
use App\Models\CoinLedgerEntry;
use App\Models\QuizAttempt;
use App\Models\StudentWallet;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DatabaseAttemptRewarder implements AttemptRewarder
{
    public function reward(QuizAttempt $attempt): ?AttemptCoinReward
    {
        if (! GamificationEnvironment::enabled()) {
            return null;
        }

        return DB::transaction(function () use ($attempt): ?AttemptCoinReward {
            $lockedAttempt = QuizAttempt::query()
                ->with('student')
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            $existing = AttemptCoinReward::query()
                ->where('attempt_id', $lockedAttempt->id)
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            if ($lockedAttempt->status !== 'submitted'
                || $lockedAttempt->student?->role !== AccountRole::Student
                || ! $lockedAttempt->answers()->whereNotNull('selected_choice_id')->exists()) {
                return null;
            }

            $coins = (int) config('gamification.completion_reward');
            if ($coins <= 0) {
                throw new DomainException('The configured completion reward must be positive.');
            }

            StudentWallet::query()->insertOrIgnore([
                'student_id' => $lockedAttempt->student_id,
                'balance' => 0,
                'lock_version' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $wallet = StudentWallet::query()->lockForUpdate()->findOrFail($lockedAttempt->student_id);
            $wallet->forceFill([
                'balance' => $wallet->balance + $coins,
                'lock_version' => $wallet->lock_version + 1,
            ])->save();

            $entry = new CoinLedgerEntry([
                'entry_type' => 'quiz_completion_reward',
                'amount' => $coins,
                'balance_after' => $wallet->balance,
                'occurred_at' => now(),
            ]);
            $entry->student()->associate($lockedAttempt->student);
            $entry->attempt()->associate($lockedAttempt);
            $entry->save();

            $reward = new AttemptCoinReward([
                'coins' => $coins,
                'awarded_at' => now(),
            ]);
            $reward->attempt()->associate($lockedAttempt);
            $reward->student()->associate($lockedAttempt->student);
            $reward->ledgerEntry()->associate($entry);
            $reward->save();

            return $reward;
        }, 3);
    }
}
