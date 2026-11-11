<?php

namespace App\Identity\Support;

final class ProductionRegistrationEnvironment
{
    public static function enabled(): bool
    {
        return app()->environment('production')
            && config('database.default') === 'pgsql'
            && HostedRegistrationControls::enabled();
    }
}
