<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'status',
        'started_at',
        'quiz_closes_at',
        'session_deadline_at',
        'effective_deadline_at',
        'last_saved_at',
        'submitted_at',
        'finalized_at',
        'finalization_reason',
        'result_released_at',
        'result_released_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'quiz_closes_at' => 'immutable_datetime',
            'session_deadline_at' => 'immutable_datetime',
            'effective_deadline_at' => 'immutable_datetime',
            'last_saved_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'finalized_at' => 'immutable_datetime',
            'result_released_at' => 'immutable_datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(QuizRevision::class, 'quiz_revision_id');
    }

    public function resultReleasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_released_by');
    }

    public function questionSnapshots(): HasMany
    {
        return $this->hasMany(AttemptQuestionSnapshot::class, 'attempt_id')->orderBy('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }
}
