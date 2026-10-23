<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending verification',
            self::Disabled => 'Disabled',
        };
    }
}
