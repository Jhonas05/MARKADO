<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWallet extends Model
{
    protected $primaryKey = 'student_id';

    public $incrementing = false;

    protected $fillable = ['balance', 'equipped_character_id', 'lock_version'];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function equippedCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'equipped_character_id');
    }
}
