<?php

namespace App\Identity\Support;

final class HostedRegistrationControls
{
    public static function enabled(): bool
    {
        return self::baseControlsEnabled()
            && (config('identity.student_claim_signup_enabled') || self::hasTrustedRosterConfiguration())
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

        return config('app.debug') === false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($host)
            && $host !== ''
            && ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }

    public static function hasTransactionalMailTransport(): bool
    {
        $from = config('mail.from.address');
        $fromName = config('mail.from.name');
        if (! is_string($from) || filter_var($from, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/[\r\n]/', $from) === 1
            || ! is_string($fromName) || preg_match('/[\r\n]/', $fromName) === 1
            || ! filled(config('app.key'))) {
            return false;
        }

        return match (config('identity.institutional_email_verification_delivery_driver')) {
            'laravel-mail' => self::hasRemoteSmtpTransport(),
            'resend-http' => self::hasResendHttpTransport(),
            default => false,
        };
    }

    private static function hasRemoteSmtpTransport(): bool
    {
        if (config('mail.default') !== 'smtp') {
            return false;
        }

        $host = config('mail.mailers.smtp.host');
        $scheme = config('mail.mailers.smtp.scheme');
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');

        return is_string($host)
            && $host !== ''
            && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            && filter_var($host, FILTER_VALIDATE_IP) === false
            && strtolower($host) !== 'localhost'
            && $scheme === 'smtps'
            && blank(config('mail.mailers.smtp.url'))
            && is_string($username) && trim($username) !== ''
            && is_string($password) && trim($password) !== '';
    }

    private static function hasResendHttpTransport(): bool
    {
        $key = config('services.resend.key');

        return is_string($key)
            && trim($key) !== ''
            && preg_match('/[\r\n]/', $key) !== 1;
    }
}
