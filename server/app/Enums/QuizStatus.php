<?php

namespace App\Enums;

enum QuizStatus: string
{
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
        };
    }
}
