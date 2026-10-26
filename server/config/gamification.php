<?php

return [
    'enabled' => (bool) env('MARKADO_GAMIFICATION_ENABLED', false),

    'completion_reward' => (int) env('MARKADO_GAMIFICATION_COMPLETION_REWARD', 10),

    'characters' => [
        [
            'id' => '90000000-0000-4000-8000-000000000001',
            'slug' => 'byte',
            'name' => 'Byte',
            'description' => 'A bright little study robot who keeps every quiz session moving.',
            'price' => (int) env('MARKADO_GAMIFICATION_BYTE_PRICE', 0),
            'artwork_path' => '/characters/byte-v2.png',
            'starter' => true,
        ],
        [
            'id' => '90000000-0000-4000-8000-000000000002',
            'slug' => 'mochi',
            'name' => 'Mochi',
            'description' => 'A calm study spirit who brings a little focus to every quiz session.',
            'price' => (int) env('MARKADO_GAMIFICATION_MOCHI_PRICE', 10),
            'artwork_path' => '/characters/mochi-v2.png',
            'starter' => false,
        ],
        [
            'id' => '90000000-0000-4000-8000-000000000003',
            'slug' => 'pixel',
            'name' => 'Pixel',
            'description' => 'A curious geometric creature built from focus, energy, and small wins.',
            'price' => (int) env('MARKADO_GAMIFICATION_PIXEL_PRICE', 20),
            'artwork_path' => '/characters/pixel-v2.png',
            'starter' => false,
        ],
    ],
];
