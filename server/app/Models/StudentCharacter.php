<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCharacter extends Model
{
    use HasUuids;

    protected $fillable = ['student_id', 'character_id', 'acquisition_type', 'price_paid', 'acquired_at'];

    protected function casts(): array
    {
        return [
            'price_paid' => 'integer',
            'acquired_at' => 'immutable_datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
