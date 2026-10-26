<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptCoinReward extends Model
{
    use HasUuids;

    protected $fillable = ['coins', 'awarded_at'];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'awarded_at' => 'immutable_datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(CoinLedgerEntry::class);
    }
}
