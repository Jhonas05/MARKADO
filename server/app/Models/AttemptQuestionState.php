<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptQuestionState extends Model
{
    use HasUuids;

    protected $fillable = [
        'status',
        'started_at',
        'deadline_at',
        'locked_at',
        'lock_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'deadline_at' => 'immutable_datetime',
            'locked_at' => 'immutable_datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function questionSnapshot(): BelongsTo
    {
        return $this->belongsTo(AttemptQuestionSnapshot::class, 'question_snapshot_id');
    }
}
