<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class AttemptQuestionSnapshot extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'source_question_id',
        'position',
        'type',
        'prompt',
        'choices',
        'correct_choice_id',
    ];

    protected $hidden = [
        'correct_choice_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'choices' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Attempt question snapshots are immutable.'));
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'source_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'question_snapshot_id');
    }
}
