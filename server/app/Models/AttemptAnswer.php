<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    use HasUuids;

    protected $fillable = [
        'selected_choice_id',
        'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'saved_at' => 'immutable_datetime',
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
