<?php

namespace App\Identity\Support;

use App\Identity\Contracts\InstitutionalEmailPolicy;

final class ConfiguredInstitutionalEmailPolicy implements InstitutionalEmailPolicy
{
    public function normalizeAllowedAddress(string $email): ?string
    {
        if ($email === '' || $email !== trim($email) || substr_count($email, '@') !== 1) {
            return null;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $normalizedDomain = $this->normalizeDomain($domain);

        if ($localPart === '' || $normalizedDomain === null) {
            return null;
        }

        $normalizedEmail = $localPart.'@'.$normalizedDomain;
        if (filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $allowedDomains = array_values(array_filter(array_map(
            fn (mixed $allowedDomain): ?string => is_string($allowedDomain)
                ? $this->normalizeDomain($allowedDomain)
                : null,
            (array) config('identity.institutional_email_domains', []),
        )));

        return in_array($normalizedDomain, $allowedDomains, true)
            ? $normalizedEmail
            : null;
    }

    private function normalizeDomain(string $domain): ?string
    {
        if ($domain === '' || $domain !== trim($domain) || str_ends_with($domain, '.')) {
            return null;
        }

        $asciiDomain = preg_match('/[^\x20-\x7E]/', $domain) === 1
            ? $this->internationalDomainToAscii($domain)
            : $domain;

        if ($asciiDomain === null) {
            return null;
        }

        $asciiDomain = strtolower($asciiDomain);

        return filter_var($asciiDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            ? $asciiDomain
            : null;
    }

    private function internationalDomainToAscii(string $domain): ?string
    {
        if (! function_exists('idn_to_ascii')) {
            return null;
        }

        $normalized = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return $normalized === false ? null : $normalized;
    }
}
