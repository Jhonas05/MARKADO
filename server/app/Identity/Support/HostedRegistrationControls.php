<?php

namespace App\Identity\Support;

final class HostedRegistrationControls
{
    public static function enabled(): bool
    {
        return self::baseControlsEnabled()
            && self::hasTrustedRosterConfiguration()
            && self::hasSecurePublicOrigin()
            && self::hasTransactionalMailTransport();
    }

    public static function baseControlsEnabled(): bool
    {
        return config('identity.public_student_registration_enabled')
            && config('identity.student_self_registration_enabled')
            && config('identity.automatic_student_activation_enabled')
            && config('identity.institutional_email_verification_enabled')
            && config('identity.institutional_email_verification_delivery_enabled')
            && is_int(config('identity.institutional_email_verification_hourly_limit'))
            && config('identity.institutional_email_verification_hourly_limit') >= 1
            && config('identity.institutional_email_verification_hourly_limit') <= 5
            && config('identity.institutional_email_domains') !== [];
    }

    public static function hasTrustedRosterConfiguration(): bool
    {
        $source = config('identity.trusted_roster_source');
        $maxAge = config('identity.trusted_roster_max_age_hours');

        return is_string($source)
            && $source !== ''
            && strlen($source) <= 120
            && is_int($maxAge)
            && $maxAge >= 1
            && $maxAge <= 168;
    }

    public static function hasSecurePublicOrigin(): bool
    {
        $url = (string) config('app.url');
        $host = parse_url($url, PHP_URL_HOST);

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($host)
            && $host !== ''
            && ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }

    public static function hasTransactionalMailTransport(): bool
    {
        if (! filled(config('mail.from.address')) || config('mail.default') !== 'smtp') {
            return false;
        }

        $host = config('mail.mailers.smtp.host');

        return is_string($host)
            && $host !== ''
            && ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }
}
