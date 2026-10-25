<?php

namespace App\Models;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'account_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => AccountRole::class,
            'account_status' => AccountStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
        });
    }

    public function ownedQuizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'owner_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }

    public function releasedQuizResults(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'result_released_by');
    }

    public function quizAccessEvents(): HasMany
    {
        return $this->hasMany(QuizAccessEvent::class);
    }

    public function schoolStudentRecord(): HasOne
    {
        return $this->hasOne(SchoolStudentRecord::class);
    }

    public function studentIdentityEvents(): HasMany
    {
        return $this->hasMany(StudentIdentityEvent::class);
    }
}
