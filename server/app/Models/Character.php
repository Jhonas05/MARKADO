<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'slug',
        'name',
        'description',
        'price',
        'artwork_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function owners(): HasMany
    {
        return $this->hasMany(StudentCharacter::class);
    }
}
