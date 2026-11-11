<?php

$configuredDomains = array_values(array_filter(array_map(
    static fn (string $domain): string => trim($domain),
    explode(',', (string) env('MARKADO_INSTITUTIONAL_EMAIL_DOMAINS', '')),
)));

$configuredHourlyLimit = env('MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_HOURLY_LIMIT', 5);
$configuredHourlyLimit = $configuredHourlyLimit === '' ? 5 : $configuredHourlyLimit;
$validatedLimit = is_int($configuredHourlyLimit) || is_string($configuredHourlyLimit)
    ? filter_var($configuredHourlyLimit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]])
    : false;
$hourlyLimit = $validatedLimit === false ? null : $validatedLimit;

$configuredRosterMaxAge = env('MARKADO_TRUSTED_ROSTER_MAX_AGE_HOURS');
$validatedRosterMaxAge = is_int($configuredRosterMaxAge) || is_string($configuredRosterMaxAge)
    ? filter_var($configuredRosterMaxAge, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 168]])
    : false;
$rosterMaxAgeHours = $validatedRosterMaxAge === false ? null : $validatedRosterMaxAge;

return [
    'presentation_registration_enabled' => (bool) env('MARKADO_PRESENTATION_REGISTRATION_ENABLED', false),
    'public_student_registration_enabled' => (bool) env('MARKADO_PUBLIC_STUDENT_REGISTRATION_ENABLED', false),
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
    | The approved issue/resend ceiling is five per rolling hour (PR #24 policy,
    | reconciled in ADR-013). Invalid or higher limits fail closed. All live
    | operations remain gated; v9.4 enables only a guarded local presentation.
    */
    'institutional_email_verification_enabled' => (bool) env('MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_ENABLED', false),
    'institutional_email_verification_delivery_enabled' => (bool) env('MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_ENABLED', false),
    'institutional_email_verification_lifetime_minutes' => 30,
    'institutional_email_verification_resend_cooldown_seconds' => 60,
    'institutional_email_verification_hourly_limit' => $hourlyLimit,

    /*
    | Production registration additionally requires source-tagged roster rows.
    | Neither value has a product default: the institution must name its real
    | authoritative feed and approve how stale a row may be before access fails.
    */
    'trusted_roster_source' => trim((string) env('MARKADO_TRUSTED_ROSTER_SOURCE', '')),
    'trusted_roster_max_age_hours' => $rosterMaxAgeHours,
];
