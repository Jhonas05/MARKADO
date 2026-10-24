<?php

return [
    /*
    | Normal assessment routes remain fail-closed until identity and operations
    | owners explicitly activate them. Tests may override these values through
    | trusted server configuration; browser input can never enable them.
    */
    'routes_enabled' => (bool) env('MARKADO_ASSESSMENT_ROUTES_ENABLED', false),
    'expiration_enabled' => (bool) env('MARKADO_ASSESSMENT_EXPIRATION_ENABLED', false),
    'expiration_batch_size' => (int) env('MARKADO_ASSESSMENT_EXPIRATION_BATCH_SIZE', 100),
    'display_timezone' => 'Asia/Manila',
    'scoring_version' => 'v1-objective-1-point',
];
