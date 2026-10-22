<?php

namespace App\Assessment\Contracts;

use DateTimeImmutable;

interface AuthoritativeClock
{
    public function now(): DateTimeImmutable;
}
