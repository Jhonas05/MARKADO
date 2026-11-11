<?php

return [
    /*
    | Hosted presentation mode is intentionally separate from both the
    | loopback-only demo and production. Every value is required at runtime;
    | the repository does not invent an environment or demo mailbox.
    */
    'hosted_enabled' => (bool) env('MARKADO_HOSTED_PRESENTATION_ENABLED', false),
    'expected_url' => rtrim(trim((string) env('MARKADO_HOSTED_PRESENTATION_URL', '')), '/'),
    'environment_id' => trim((string) env('MARKADO_HOSTED_PRESENTATION_ENVIRONMENT_ID', '')),
    'environment_name' => trim((string) env('MARKADO_HOSTED_PRESENTATION_ENVIRONMENT_NAME', '')),
    'runtime_environment_id' => trim((string) env('RAILWAY_ENVIRONMENT_ID', '')),
    'runtime_environment_name' => trim((string) env('RAILWAY_ENVIRONMENT_NAME', '')),
    'production_url' => rtrim(trim((string) env(
        'MARKADO_PRODUCTION_URL',
        'https://markado-app-production.up.railway.app',
    )), '/'),

    'accounts' => [
        'administrator_email' => trim((string) env('MARKADO_PRESENTATION_ADMIN_EMAIL', '')),
        'professor_email' => trim((string) env('MARKADO_PRESENTATION_PROFESSOR_EMAIL', '')),
        'fallback_student_email' => trim((string) env('MARKADO_PRESENTATION_FALLBACK_STUDENT_EMAIL', '')),
        'fallback_student_number' => trim((string) env('MARKADO_PRESENTATION_FALLBACK_STUDENT_NUMBER', '')),
        'registration_email' => trim((string) env('MARKADO_PRESENTATION_REGISTRATION_EMAIL', '')),
        'registration_student_number' => trim((string) env('MARKADO_PRESENTATION_REGISTRATION_STUDENT_NUMBER', '')),
    ],
];
