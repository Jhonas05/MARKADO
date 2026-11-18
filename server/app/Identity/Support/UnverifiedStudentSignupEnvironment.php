<?php

namespace App\Identity\Support;

use Illuminate\Http\Request;

final class UnverifiedStudentSignupEnvironment
{
    /**
     * This mode creates a login account, not verified school identity.
     * It cannot run alongside the institutional verification signup route.
     */
    public static function enabled(): bool
    {
        if (! config('identity.unverified_student_signup_enabled')
            || config('identity.presentation_registration_enabled')
            || config('identity.public_student_registration_enabled')
            || ! filled(config('app.key'))) {
            return false;
        }

        if (app()->environment('testing')) {
            return true;
        }

        if (app()->environment('local')) {
            return config('database.default') === 'pgsql'
                && in_array(parse_url((string) config('app.url'), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true);
        }

        if (app()->environment(['staging', 'production'])) {
            $url = (string) config('app.url');
            $host = parse_url($url, PHP_URL_HOST);

            return config('database.default') === 'pgsql'
                && config('app.debug') === false
                && parse_url($url, PHP_URL_SCHEME) === 'https'
                && is_string($host)
                && $host !== ''
                && ! in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
        }

        return false;
    }

    public static function availableFor(Request $request): bool
    {
        if (! self::enabled()) {
            return false;
        }

        if (app()->environment('testing')) {
            return true;
        }

        if (app()->environment('local')) {
            return in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true)
                && in_array($request->ip(), ['127.0.0.1', '::1'], true);
        }

        $expectedHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $request->isSecure()
            && is_string($expectedHost)
            && hash_equals(strtolower($expectedHost), strtolower($request->getHost()));
    }
}
