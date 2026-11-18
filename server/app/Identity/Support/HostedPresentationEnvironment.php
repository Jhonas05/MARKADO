<?php

namespace App\Identity\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HostedPresentationEnvironment
{
    public const GUARD_KEY = 'markado-hosted-presentation-v1';

    public const GUARD_TABLE = 'hosted_presentation_environment_guards';

    public static function configurationIsSafe(): bool
    {
        $url = rtrim((string) config('app.url'), '/');
        $expectedUrl = (string) config('presentation.expected_url');
        $productionUrl = (string) config('presentation.production_url');
        $environmentId = (string) config('presentation.environment_id');
        $environmentName = (string) config('presentation.environment_name');

        return app()->environment('staging')
            && config('presentation.hosted_enabled')
            && config('database.default') === 'pgsql'
            && $expectedUrl !== ''
            && hash_equals($expectedUrl, $url)
            && ! hash_equals($productionUrl, $url)
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && ! in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost', '::1', null, false], true)
            && $environmentId !== ''
            && hash_equals($environmentId, (string) config('presentation.runtime_environment_id'))
            && $environmentName !== ''
            && hash_equals($environmentName, (string) config('presentation.runtime_environment_name'))
            && filled(config('database.connections.pgsql.database'));
    }

    public static function enabled(): bool
    {
        if (! self::configurationIsSafe()) {
            return false;
        }

        try {
            $database = DB::selectOne('select current_database() as database_name');
            if (! is_string($database?->database_name)
                || ! hash_equals((string) config('database.connections.pgsql.database'), $database->database_name)
                || ! Schema::hasTable(self::GUARD_TABLE)) {
                return false;
            }

            return DB::table(self::GUARD_TABLE)
                ->where('guard_key', self::GUARD_KEY)
                ->where('environment_id', config('presentation.environment_id'))
                ->where('environment_name', config('presentation.environment_name'))
                ->where('app_url', rtrim((string) config('app.url'), '/'))
                ->where('database_name', $database->database_name)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
