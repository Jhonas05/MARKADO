<?php

namespace App\Models;

use App\Enums\QuizStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'course_code',
        'course_name',
        'instructions',
        'opens_at',
        'closes_at',
        'whole_quiz_duration_seconds',
        'per_question_duration_seconds',
        'randomize_questions',
        'randomize_choices',
    ];

    protected $hidden = [
        'access_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuizStatus::class,
            'opens_at' => 'immutable_datetime',
            'closes_at' => 'immutable_datetime',
            'whole_quiz_duration_seconds' => 'integer',
            'per_question_duration_seconds' => 'integer',
            'randomize_questions' => 'boolean',
            'randomize_choices' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(QuizRevision::class)->orderByDesc('revision_number');
    }

    public function currentPublishedRevision(): BelongsTo
    {
        return $this->belongsTo(QuizRevision::class, 'current_published_revision_id');
    }

    public function activeDraftRevision(): BelongsTo
    {
        return $this->belongsTo(QuizRevision::class, 'active_draft_revision_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(QuizStudentAssignment::class);
    }

    public function sectionAssignments(): HasMany
    {
        return $this->hasMany(QuizSectionAssignment::class);
    }

    public function publicationEvents(): HasMany
    {
        return $this->hasMany(QuizPublicationEvent::class);
    }

    public function accessEvents(): HasMany
    {
        return $this->hasMany(QuizAccessEvent::class);
    }

    public function assessmentAuditEvents(): HasMany
    {
        return $this->hasMany(AssessmentAuditEvent::class);
    }
}
