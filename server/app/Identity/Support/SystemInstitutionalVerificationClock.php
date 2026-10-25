<?php

namespace App\Identity\Support;

use App\Identity\Contracts\InstitutionalVerificationClock;
use Carbon\CarbonImmutable;

final class SystemInstitutionalVerificationClock implements InstitutionalVerificationClock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }
}
