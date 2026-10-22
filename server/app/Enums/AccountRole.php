<?php

namespace App\Enums;

enum AccountRole: string
{
    case Student = 'student';
    case Instructor = 'instructor';
    case AcademicAdministrator = 'academic_administrator';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Instructor => 'Instructor',
            self::AcademicAdministrator => 'Academic administrator',
        };
    }
}
