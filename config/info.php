<?php

return [

    /**
     * Each of these refer to the information pages.
     */
    'home'              => [],
    'character-stats'   => [],
    'movement'          => [],
    'time-gates'        => [],
    'rules'             => [],
    'map'               => [],
    'notifications'     => [],
    'settings'          => [],
    'market-board'      => [],
    'equipment'         => [],
    'traverse'          => [],
    'teleport'          => [],
    'planes'            => [],
    'kingdoms'          => [
        [
            'livewire'            => true,
            'view'                => 'admin.kingdoms.units.data-table',
            'only'                => null,
            'insert_before_table' => null,
        ],
    ],
    'locations'         => [
        [
            'livewire'            => true,
            'view'                => 'admin.locations.data-table',
            'only'                => null,
            'insert_before_table' => null,
        ],
    ],
    'set-sail'          => [],
    'attacking-kingdoms' => [
        [
            'livewire'            => true,
            'view'                => 'admin.kingdoms.units.data-table',
            'only'                => null,
            'insert_before_table' => null,
        ],
    ],
    'races-and-classes' => [
        [
            'livewire'            => true,
            'view'                => 'admin.races.data-table',
            'only'                => null,
            'insert_before_table' => null,
        ],
        [
            'livewire'            => true,
            'view'                => 'admin.classes.data-table',
            'only'                => null,
            'insert_before_table' => null,
        ]
    ],
    'skill-information' => [
        [
            'livewire'            => true,
            'view'                => 'admin.skills.data-table',
            'only'                => null,
            'insert_before_table' => null,
        ]
    ],
    'adventure' => [
        [
            'livewire'            => true,
            'view'                => 'admin.adventures.data-table',
            'only'                => null,
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
            'livewire'            => true,
            'view'                => 'admin.items.data-table',
            'only'                => 'quest-items-book',
            'insert_before_table' => null,
        ]
    ],
    'enchanting' => [
        [
            'livewire'            => true,
            'view'                => 'admin.affixes.data-table',
            'insert_before_table' => null,
            'only'                => null,
        ]
    ],
    'monsters' => [
        [
            'livewire'            => true,
            'view'                => 'admin.monsters.data-table',
            'only'                => null,
            'insert_before_table' => null,
        ]
    ],
];
