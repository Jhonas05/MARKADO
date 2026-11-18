<?php

namespace App\Identity\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class RegistrationEnvironment
{
    public static function enabled(): bool
    {
        $configured = LocalRegistrationEnvironment::enabled()
            || HostedPresentationRegistrationEnvironment::enabled()
            || ProductionRegistrationEnvironment::enabled();

        if (! $configured || ! config('identity.student_claim_signup_enabled')) {
            return $configured;
        }

        try {
            return Schema::hasTable('student_signup_claims');
        } catch (Throwable) {
            return false;
        }
    }

    public static function claimSignupEnabled(): bool
    {
        return config('identity.student_claim_signup_enabled') && self::enabled();
    }

    public static function canStart(Request $request): bool
    {
        return Route::has('registration.store') && self::availableFor($request);
    }

    public static function availableFor(Request $request): bool
    {
        if (! self::enabled()) {
            return false;
        }

        if (! app()->environment('testing') && LocalRegistrationEnvironment::enabled()
            && (! in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1'], true)
                || ! in_array($request->ip(), ['127.0.0.1', '::1'], true))) {
            return false;
        }

        if (ProductionRegistrationEnvironment::enabled()) {
            $expectedHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (! $request->isSecure() || ! is_string($expectedHost)
                || ! hash_equals(strtolower($expectedHost), strtolower($request->getHost()))) {
                return false;
            }
        }

        if (HostedPresentationRegistrationEnvironment::enabled()
            && (! $request->isSecure()
                || ! hash_equals((string) parse_url((string) config('app.url'), PHP_URL_HOST), $request->getHost()))) {
            return false;
        }

        return true;
    }
}
