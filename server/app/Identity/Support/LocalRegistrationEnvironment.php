<?php

namespace App\Identity\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class LocalRegistrationEnvironment
{
    public static function enabled(): bool
    {
        if (! config('identity.presentation_registration_enabled')
            || ! config('identity.student_self_registration_enabled')
            || ! config('identity.automatic_student_activation_enabled')
            || ! config('identity.institutional_email_verification_enabled')
            || ! config('identity.institutional_email_verification_delivery_enabled')
            || ! is_int(config('identity.institutional_email_verification_hourly_limit'))
            || config('identity.institutional_email_verification_hourly_limit') < 1
            || config('identity.institutional_email_verification_hourly_limit') > 5
            || config('identity.institutional_email_domains') === []) {
            return false;
        }

        if (app()->environment('testing')) {
            return (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:')
                || (config('database.default') === 'pgsql' && (config('database.connections.pgsql.database') === 'markado_ci'
                    || preg_match('/\Amarkado_test(?:_[a-z0-9]+)*\z/D', (string) config('database.connections.pgsql.search_path')) === 1));
        }

        if (! app()->environment('local') || config('database.default') !== 'pgsql'
            || config('mail.default') !== 'smtp'
            || ! in_array(config('mail.mailers.smtp.host'), ['127.0.0.1', 'localhost', '::1'], true)
            || ! in_array(config('database.connections.pgsql.host'), ['127.0.0.1', 'localhost', '::1'], true)
            || ! in_array(parse_url((string) config('app.url'), PHP_URL_HOST), ['127.0.0.1', 'localhost', '::1'], true)
            || filled(config('mail.mailers.smtp.url'))) {
            return false;
        }

        $schema = (string) config('database.connections.pgsql.search_path');
        if (! preg_match('/\Amarkado_demo_v89_registration_[a-z0-9_]+\z/D', $schema)) {
            return false;
        }

        try {
            $server = DB::selectOne('select current_schema() as schema_name, inet_server_addr()::text as address');

            return $server?->schema_name === $schema
                && in_array(explode('/', (string) $server?->address, 2)[0], ['127.0.0.1', '::1'], true)
                && Schema::hasTable('markado_demo_environment_guard')
                && DB::table('markado_demo_environment_guard')
                    ->where('guard_key', 'markado-v8-9-functional-assessment-demo')
                    ->where('schema_name', $schema)->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
