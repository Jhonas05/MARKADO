<?php

return [
    'enabled' => (bool) env('MARKADO_GAMIFICATION_ENABLED', false),

    'completion_reward' => (int) env('MARKADO_GAMIFICATION_COMPLETION_REWARD', 10),

    'characters' => [
        [
            'id' => '90000000-0000-4000-8000-000000000001',
            'slug' => 'byte',
            'name' => 'Byte',
            'description' => 'A bright little robot who keeps every study streak moving.',
            'price' => (int) env('MARKADO_GAMIFICATION_BYTE_PRICE', 0),
            'artwork_path' => '/characters/byte.svg',
            'starter' => true,
        ],
        [
            'id' => '90000000-0000-4000-8000-000000000002',
            'slug' => 'mochi',
            'name' => 'Mochi',
            'description' => 'A calm abstract companion for thoughtful quiz sessions.',
            'price' => (int) env('MARKADO_GAMIFICATION_MOCHI_PRICE', 10),
            'artwork_path' => '/characters/mochi.svg',
            'starter' => false,
        ],
        [
            'id' => '90000000-0000-4000-8000-000000000003',
            'slug' => 'pixel',
            'name' => 'Pixel',
            'description' => 'A curious geometric creature built from focus and small wins.',
            'price' => (int) env('MARKADO_GAMIFICATION_PIXEL_PRICE', 20),
            'artwork_path' => '/characters/pixel.svg',
            'starter' => false,
        ],
    ],
];
