<?php

$configuredDomains = array_values(array_filter(array_map(
    static fn (string $domain): string => trim($domain),
    explode(',', (string) env('MARKADO_INSTITUTIONAL_EMAIL_DOMAINS', '')),
)));

return [
    /*
    | Student onboarding is default-deny. Production domains must come from
    | trusted server configuration and are never accepted from request input.
    */
    'institutional_email_domains' => $configuredDomains,
];
