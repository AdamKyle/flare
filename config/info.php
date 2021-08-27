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
    'planes'            => [
        [
            'livewire'            => true,
            'view'                => 'admin.maps.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'celestials'        => [
        [
            'livewire'            => true,
            'view'                => 'admin.monsters.data-table',
            'only'                => 'celestials',
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
        [
            'livewire'            => true,
            'view'                => 'admin.npcs.data-table',
            'only'                => \App\Flare\Values\NpcTypes::SUMMONER,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'disenchanting'     => [],
    'npc-kingdoms'      => [],
    'equipment-sets'    => [],
    'npcs'              => [
        [
            'livewire'            => true,
            'view'                => 'admin.npcs.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'currencies'        => [],
    'quests'            => [
        [
            'livewire'            => true,
            'view'                => 'admin.quests.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'usable-items'      => [
        [
            'livewire'            => true,
            'view'                => 'admin.items.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => 'alchemy',
            'craft_only'          => true,
        ],
    ],
    'combat'            => [],
    'items-and-kingdoms' => [],
    'kingdoms'          => [
        [
            'livewire'            => true,
            'view'                => 'admin.kingdoms.buildings.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
        [
            'livewire'            => true,
            'view'                => 'admin.kingdoms.units.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
    ],
    'locations'         => [
        [
            'livewire'            => true,
            'view'                => 'admin.locations.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
    ],
    'set-sail'          => [],
    'attacking-kingdoms' => [
        [
            'livewire'            => true,
            'view'                => 'admin.kingdoms.units.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
    ],
    'races-and-classes' => [
        [
            'livewire'            => true,
            'view'                => 'admin.races.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
        [
            'livewire'            => true,
            'view'                => 'admin.classes.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'skill-information' => [
        [
            'livewire'            => true,
            'view'                => 'admin.skills.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'adventure' => [
        [
            'livewire'            => true,
            'view'                => 'admin.adventures.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'crafting' => [
        [
            'livewire'            => true,
            'view'                => 'admin.items.data-table',
            'only'                => null,
            'insert_before_table' => 'information.partials.crafting-section-one',
            'showSkillInfo'       => true,
            'showDropDown'        => true,
            'type'                => null,
            'craft_only'          => false,
        ],
        [
            'livewire'            => true,
            'view'                => 'admin.items.data-table',
            'only'                => 'quest-items-book',
            'insert_before_table' => null,
            'showSkillInfo'       => false,
            'showDropDown'        => false,
            'type'                => null,
            'craft_only'          => false,
        ],
        [
            'livewire'            => true,
            'view'                => 'admin.items.data-table',
            'only'                => null,
            'insert_before_table' => 'information.partials.crafting-section-one',
            'showSkillInfo'       => true,
            'showDropDown'        => true,
            'type'                => null,
            'craft_only'          => true,
        ],
    ],
    'enchanting' => [
        [
            'livewire'            => true,
            'view'                => 'admin.affixes.data-table',
            'insert_before_table' => null,
            'only'                => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
    'monsters' => [
        [
            'livewire'            => true,
            'view'                => 'admin.monsters.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ]
    ],
];
