<?php

return [
    'name' => 'LaravelPWA',
    'manifest' => [
        'name' => env('APP_NAME', 'Planes of Tlessa'),
        'short_name' => 'PoT',
        'start_url' => '/',
        'background_color' => '#ffffff',
        'theme_color' => '#000000',
        'display' => 'standalone',
        'orientation'=> 'any',
        'status_bar'=> 'black',
        'icons' => [
            '72x72' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-72.png',
                'purpose' => 'any'
            ],
            '96x96' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-96.png',
                'purpose' => 'any'
            ],
            '128x128' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-128.png',
                'purpose' => 'any'
            ],
            '144x144' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-144.png',
                'purpose' => 'any'
            ],
            '152x152' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-152.png',
                'purpose' => 'any'
            ],
            '192x192' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-192.png',
                'purpose' => 'any'
            ],
            '384x384' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-384.png',
                'purpose' => 'any'
            ],
            '512x512' => [
                'path' => 'pwa-images/tlessa-icons/tlessa-icon-512.png',
                'purpose' => 'any'
            ],
        ],
        'splash' => [
            '640x1136' => 'pwa-images/tlessa-splash-images/tlessa-splash-640x1136.png',
            '750x1334' => 'pwa-images/tlessa-splash-images/tlessa-splash-750x1334.png',
            '828x1792' => 'pwa-images/tlessa-splash-images/tlessa-splash-828x1792.png',
            '1125x2436' => 'pwa-images/tlessa-splash-images/tlessa-splash-1125x2436.png',
            '1242x2208' => 'pwa-images/tlessa-splash-images/tlessa-splash-1242x2208.png',
            '1242x2688' => 'pwa-images/tlessa-splash-images/tlessa-splash-1242x2688.png',
            '1536x2048' => 'pwa-images/tlessa-splash-images/tlessa-splash-1536x2048.png',
            '1668x2224' => 'pwa-images/tlessa-splash-images/tlessa-splash-1668x2224.png',
            '1668x2388' => 'pwa-images/tlessa-splash-images/tlessa-splash-1668x2388.png',
            '2048x2732' => 'pwa-images/tlessa-splash-images/tlessa-splash-2048x2732.png',
        ],
        'shortcuts' => [
        ],
        'custom' => []
    ]
];
