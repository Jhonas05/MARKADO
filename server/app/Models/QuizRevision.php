<?php

namespace App\Models;

use App\Enums\QuizRevisionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class QuizRevision extends Model
{
    use HasUuids;

    protected $fillable = [
        'revision_number',
        'status',
        'title',
        'course_code',
        'course_name',
        'instructions',
        'display_timezone',
        'closes_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuizRevisionStatus::class,
            'closes_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (QuizRevision $revision): void {
            if ($revision->getRawOriginal('status') === QuizRevisionStatus::Published->value) {
                throw new LogicException('Published quiz revisions are immutable.');
            }
        });
        static::deleting(function (QuizRevision $revision): void {
            if ($revision->status === QuizRevisionStatus::Published) {
                throw new LogicException('Published quiz revisions cannot be deleted.');
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_revision_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizRevisionQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
