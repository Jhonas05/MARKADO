<?php

namespace Tests\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use DateTimeImmutable;

final class MutableAuthoritativeClock implements AuthoritativeClock
{
    public function __construct(private DateTimeImmutable $current) {}

    public function now(): DateTimeImmutable
    {
        return $this->current;
    }

    public function advanceTo(DateTimeImmutable $instant): void
    {
        $this->current = $instant;
    }
}
