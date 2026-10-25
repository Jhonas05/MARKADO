<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionalEmailVerificationChallenge extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = [
        'token_hash',
        'email_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
