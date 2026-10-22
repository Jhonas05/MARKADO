<?php

namespace App\Support;

use App\Models\Quiz;

class QuizAccessCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        do {
            $code = '';

            for ($index = 0; $index < 8; $index++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (Quiz::query()->where('access_code', $code)->exists());

        return $code;
    }
}
