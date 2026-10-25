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

    /*
    | Direction is approved, but public mutations and the operational security
    | gate remain default-off until the school-specific controls are approved.
    */
    'student_self_registration_enabled' => (bool) env('MARKADO_STUDENT_SELF_REGISTRATION_ENABLED', false),
    'automatic_student_activation_enabled' => (bool) env('MARKADO_STUDENT_AUTO_ACTIVATION_ENABLED', false),
];
