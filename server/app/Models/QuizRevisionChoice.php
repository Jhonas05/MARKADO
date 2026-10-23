<?php

namespace App\Models;

use App\Enums\QuizRevisionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class QuizRevisionChoice extends Model
{
    use HasUuids;

    protected $fillable = ['choice_text', 'position', 'is_correct', 'source_choice_id'];

    protected $hidden = ['is_correct'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    protected static function booted(): void
    {
        $guard = function (QuizRevisionChoice $choice): void {
            $published = $choice->question()
                ->whereHas('revision', fn ($query) => $query->where('status', QuizRevisionStatus::Published->value))
                ->exists();

            if ($published) {
                throw new LogicException('Choices in a published revision are immutable.');
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizRevisionQuestion::class, 'revision_question_id');
    }

    public function sourceChoice(): BelongsTo
    {
        return $this->belongsTo(QuizChoice::class, 'source_choice_id');
    }
}
