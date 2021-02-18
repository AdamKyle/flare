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
            'insert_before_table' => null,
        ], 
        [
            'livewire' => true,
            'view'    => 'admin.classes.data-table',
            'only' => null,
            'insert_before_table' => null,
        ]
    ],

    'races-and-classes' => [
        [
            'livewire' => true,
            'view'    => 'admin.races.data-table',
            'only' => null,
            'insert_before_table' => null,
        ], 
        [
            'livewire' => true,
            'view'    => 'admin.classes.data-table',
            'only' => null,
            'insert_before_table' => null,
        ]
    ],

    'skill-information' => [
        [
            'livewire' => true,
            'view'     => 'admin.skills.data-table',
            'only' => null,
            'insert_before_table' => null,
        ]
    ],

    'home' => [],
    'character-stats' => [],
    'movement' => [],

    'adventure' => [
        [
            'livewire' => true,
            'view'     => 'admin.adventures.data-table',
            'only' => null,
            'insert_before_table' => null,
        ]
    ],
    'crafting' => [
        [
            'livewire'            => true,
            'view'                => 'admin.items.data-table',
            'only'                => null,
            'insert_before_table' => 'information.partials.crafting-section-one',
        ],
        [
            'livewire' => true,
            'view'     => 'admin.items.data-table',
            'only'     => 'quest-items-book',
            'insert_before_table' => null,
        ]
    ],
    'enchanting' => [
        [
            'livewire' => true,
            'view'     => 'admin.affixes.data-table',
            'only' => null,
            'insert_before_table' => null,
        ]
    ],
    'kingdoms' => [],
    'time-gates' => [],
    'rules' => [],
    'monsters' => [
        [
            'livewire' => true,
            'view' => 'admin.monsters.data-table',
            'only' => null,
            'insert_before_table' => null,
        ]
    ],
    'map' => [],
    'notifications' => [],
];