<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptResult extends Model
{
    use HasUuids;

    protected $fillable = [
        'attempt_id',
        'status',
        'scoring_version',
        'earned_points',
        'maximum_points',
        'scored_at',
        'failure_code',
        'released_at',
        'released_by',
    ];

    protected function casts(): array
    {
        return [
            'earned_points' => 'integer',
            'maximum_points' => 'integer',
            'scored_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
