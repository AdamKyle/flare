<?php

return [

    /**
     * These are all the skills for the game
     */
     'skills' => [
         [
            'name'                      => 'Accuracy',
            'description'               => 'Helps in Determining the accuracy of your attack.',
            'base_damage_mod'           => 0.0,
            'base_healing_mod'          => 0.0,
            'base_ac_mod'               => 0.0,
            'fight_time_out_mod'        => 0.0,
            'move_time_out_mod'         => 0.0,
            'can_train'                 => true,
            'max_level'                 => 100,
            'xp_max'                    => rand(100, 350),
            'skill_bonus_per_level'     => 0.01
         ],
         [
            'name'                      => 'Dodge',
            'description'               => 'Helps in Determining if you can dodge the attack.',
            'base_damage_mod'           => 0.0,
            'base_healing_mod'          => 0.0,
            'base_ac_mod'               => 0.0,
            'fight_time_out_mod'        => 0.0,
            'move_time_out_mod'         => 0.0,
            'can_train'                 => true,
            'max_level'                 => 100,
            'xp_max'                    => rand(100, 350),
            'skill_bonus_per_level'     => 0.01
         ],
         [
            'name'                      => 'Looting',
            'description'               => 'Determines if you get an item or not per fight.',
            'base_damage_mod'           => 0.0,
            'base_healing_mod'          => 0.0,
            'base_ac_mod'               => 0.0,
            'fight_time_out_mod'        => 0.0,
            'move_time_out_mod'         => 0.0,
            'can_train'                 => true,
            'max_level'                 => 100,
            'xp_max'                    => rand(100, 350),
            'skill_bonus_per_level'     => 0.01
         ],
         [
            'name'                      => 'Weapon Crafting',
            'description'               => 'A skill used in crafting weapons.',
            'base_damage_mod'           => 0.0,
            'base_healing_mod'          => 0.0,
            'base_ac_mod'               => 0.0,
            'fight_time_out_mod'        => 0.0,
            'move_time_out_mod'         => 0.0,
            'can_train'                 => false,
            'max_level'                 => 400,
            'xp_max'                    => rand(50, 150),
            'skill_bonus_per_level'     => 0.25
         ],
    ],
];
