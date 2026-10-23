<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Enums\QuizRevisionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class QuizRevisionQuestion extends Model
{
    use HasUuids;

    protected $fillable = ['type', 'prompt', 'position', 'source_question_id'];

    protected function casts(): array
    {
        return ['type' => QuestionType::class];
    }

    protected static function booted(): void
    {
        $guard = function (QuizRevisionQuestion $question): void {
            if ($question->revision()->where('status', QuizRevisionStatus::Published->value)->exists()) {
                throw new LogicException('Questions in a published revision are immutable.');
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(QuizRevision::class, 'quiz_revision_id');
    }

    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'source_question_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuizRevisionChoice::class, 'revision_question_id')->orderBy('position');
    }
}
