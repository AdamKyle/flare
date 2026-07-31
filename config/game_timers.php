<?php

return [
    'development_cap' => [
        'enabled' => env('GAME_DEVELOPMENT_TIMER_CAP_ENABLED', true),
        'max_seconds' => (int) env('GAME_DEVELOPMENT_TIMER_MAX_SECONDS', 60),
        'environments' => [
            'local',
            'development',
        ],
    ],
];
