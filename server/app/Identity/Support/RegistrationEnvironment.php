<?php

namespace App\Identity\Support;

final class RegistrationEnvironment
{
    public static function enabled(): bool
    {
        return LocalRegistrationEnvironment::enabled()
            || HostedPresentationRegistrationEnvironment::enabled()
            || ProductionRegistrationEnvironment::enabled();
    }
}
