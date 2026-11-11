<?php

namespace App\Identity\Support;

final class HostedPresentationRegistrationEnvironment
{
    public static function enabled(): bool
    {
        return HostedPresentationEnvironment::enabled()
            && HostedRegistrationControls::enabled();
    }
}
