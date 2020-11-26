<?php

return [

    /**
     * Each of these refer to the information pages.
     */
    'character-information' => [
        [
            'livewire' => true,
            'view'    => 'admin.races.data-table',
            'only' => null,
        ], 
        [
            'livewire' => true,
            'view'    => 'admin.classes.data-table',
            'only' => null,
        ]
    ],

    'skill-information' => [
        [
            'livewire' => true,
            'view'     => 'admin.skills.data-table',
            'only' => null,
        ]
    ],

    'home' => [],

    'adventure' => [
        [
            'livewire' => true,
            'view'     => 'admin.adventures.data-table',
            'only' => null,
        ]
    ],
    'crafting' => [
        [
            'livewire' => true,
            'view'     => 'admin.items.data-table',
            'only'     => null,
        ],
        [
            'livewire' => true,
            'view'     => 'admin.items.data-table',
            'only'     => 'quest-items-book',
        ]
    ],
    'enchanting' => [
        [
            'livewire' => true,
            'view'     => 'admin.affixes.data-table',
            'only' => null,
        ]
    ],
    'time-gates' => [],
    'rules' => [],
    'monsters' => [
        [
            'livewire' => true,
            'view' => 'admin.monsters.data-table',
            'only' => null,
        ]
    ],
    'map' => [],
    'notifications' => [],
];