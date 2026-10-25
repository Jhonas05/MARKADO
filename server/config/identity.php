<?php

$configuredDomains = array_values(array_filter(array_map(
    static fn (string $domain): string => trim($domain),
    explode(',', (string) env('MARKADO_INSTITUTIONAL_EMAIL_DOMAINS', '')),
)));

$configuredHourlyLimit = env('MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_HOURLY_LIMIT');
$hourlyLimit = is_numeric($configuredHourlyLimit) && (int) $configuredHourlyLimit > 0
    ? (int) $configuredHourlyLimit
    : null;

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

    /*
    | ADR-011 approves the 30-minute lifetime and 60-second resend cooldown.
    | The hourly maximum and live delivery operation remain unapproved, so an
    | unset hourly limit and both false switches intentionally fail closed.
    */
    'institutional_email_verification_enabled' => (bool) env('MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_ENABLED', false),
    'institutional_email_verification_delivery_enabled' => (bool) env('MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_ENABLED', false),
    'institutional_email_verification_lifetime_minutes' => 30,
    'institutional_email_verification_resend_cooldown_seconds' => 60,
    'institutional_email_verification_hourly_limit' => $hourlyLimit,
];
