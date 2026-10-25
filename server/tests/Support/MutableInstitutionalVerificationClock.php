<?php

namespace Tests\Support;

use App\Identity\Contracts\InstitutionalVerificationClock;
use Carbon\CarbonImmutable;

final class MutableInstitutionalVerificationClock implements InstitutionalVerificationClock
{
    public function __construct(public CarbonImmutable $current) {}

    public function now(): CarbonImmutable
    {
        return $this->current;
    }
}
