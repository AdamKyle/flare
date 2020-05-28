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
         ],
         [
            'name'                      => 'Dodge',
            'description'               => 'Helps in Determining if you can dodge the attack.',
            'base_damage_mod'           => 0.0,
            'base_healing_mod'          => 0.0,
            'base_ac_mod'               => 0.0,
            'fight_time_out_mod'        => 0.0,
            'move_time_out_mod'         => 0.0,
         ],
         [
            'name'                      => 'Looting',
            'description'               => 'Determines if you get an item or not per fight.',
            'base_damage_mod'           => 0.0,
            'base_healing_mod'          => 0.0,
            'base_ac_mod'               => 0.0,
            'fight_time_out_mod'        => 0.0,
            'move_time_out_mod'         => 0.0,
         ],
    ],

    /**
     * These are the allowed Affixes:
    */
    'item_affixes' => [
        [
            'name' => 'Krawls Claw',
            'base_damage_mod' => 2,
            'type' => 'suffix',
            'description' => 'Krawl was a legendary warrior of Kazix - A province long forgotten.'
        ],
        [
            'name' => 'Gathers Hunt',
            'base_damage_mod' => 2,
            'type' => 'prefix',
            'description' => 'Once, long ago, hunters would gather and collectivly they would bring back a feast for the ages.'
        ],
    ],
];
