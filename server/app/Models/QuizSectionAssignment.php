<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizSectionAssignment extends Model
{
    use HasUuids;

    protected $fillable = ['is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
