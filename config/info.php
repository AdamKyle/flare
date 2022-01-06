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
    'voidance'          => [],
    'tips'              => [],
    'automation'        => [],
    'factions'          => [],
    'random-enchants'   => [],
    'special-locations' => [],
    'kingdom-passive-skills' => [
        [
            'livewire'            => true,
            'view'                => 'admin.passive-skills.data-table',
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
        [
            'livewire'            => false,
            'view'                => null,
            'only'                => null,
            'insert_before_table' => null,
            'type'                => null,
            'craft_only'          => false,
        ],
    ],
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
    'quest-items'            => [
        [
            'livewire'            => true,
            'view'                => 'info.quest-items.data-table',
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
    'chat-commands'     => [],
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
            'craft_only'          => true,
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
            'showSkillInfo'       => true,
            'showDropDown'        => true,
            'type'                => 'alchemy',
            'craft_only'          => true,
        ],
    ],
    'enchanting' => [
        [
            'livewire'            => false,
            'view'                => 'information.enchantments.enchantments',
            'view_attributes'     => [
                'str_mod', 'dex_mod', 'int_mod', 'chr_mod', 'dur_mod', 'agi_mod', 'focus_mod',
                'str_reduction', 'dex_reduction', 'int_reduction', 'chr_reduction', 'dur_reduction', 'agi_reduction', 'focus_reduction',
                'steal_life_amount', 'entranced_chance', 'irresistible_damage', 'class_bonus', 'base_damage_mod_bonus', 'base_healing_mod_bonus',
                'base_ac_mod_bonus', 'fight_time_out_mod_bonus', 'move_time_out_mod_bonus', 'devouring_light',
            ],
            'insert_before_table' => null,
            'only'                => null,
            'type'                => null,
            'craft_only'          => false,
        ],
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
