<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSignupClaim extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = ['user_id', 'student_number'];

    protected $hidden = ['student_number'];

    protected function casts(): array
    {
        return ['student_number' => 'encrypted'];
    }
}
