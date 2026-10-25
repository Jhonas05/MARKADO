<?php

namespace App\Identity\Contracts;

use Carbon\CarbonImmutable;

interface InstitutionalVerificationClock
{
    public function now(): CarbonImmutable;
}
