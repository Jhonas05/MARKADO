<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'status',
        'started_at',
        'quiz_closes_at',
        'session_deadline_at',
        'effective_deadline_at',
        'permutation_seed',
        'current_question_position',
        'progress_state',
        'lock_version',
        'last_saved_at',
        'submitted_at',
        'finalized_at',
        'finalization_reason',
        'terminal_receipt_id',
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
            'current_question_position' => 'integer',
            'lock_version' => 'integer',
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

    public function questionStates(): HasMany
    {
        return $this->hasMany(AttemptQuestionState::class, 'attempt_id');
    }

    public function answerReceipts(): HasMany
    {
        return $this->hasMany(AttemptAnswerReceipt::class, 'attempt_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(AttemptResult::class, 'attempt_id');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AssessmentAuditEvent::class, 'attempt_id');
    }
}
