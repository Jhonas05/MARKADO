<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSectionMembership extends Model
{
    use HasUuids;

    protected $fillable = ['is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class);
    }

    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(SchoolStudentRecord::class, 'student_record_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
