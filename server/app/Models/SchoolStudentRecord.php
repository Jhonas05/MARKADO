<?php

namespace App\Models;

use App\Enums\StudentVerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolStudentRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_number',
        'institutional_email',
        'verification_status',
        'is_active',
        'verified_at',
        'linked_at',
    ];

    protected $hidden = ['student_number', 'institutional_email'];

    protected function casts(): array
    {
        return [
            'verification_status' => StudentVerificationStatus::class,
            'is_active' => 'boolean',
            'verified_at' => 'immutable_datetime',
            'linked_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolSectionMembership::class, 'student_record_id');
    }

    public function quizAssignments(): HasMany
    {
        return $this->hasMany(QuizStudentAssignment::class, 'student_record_id');
    }
}
