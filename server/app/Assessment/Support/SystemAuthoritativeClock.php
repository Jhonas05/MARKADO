<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use DateTimeImmutable;
use DateTimeZone;

final class SystemAuthoritativeClock implements AuthoritativeClock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
