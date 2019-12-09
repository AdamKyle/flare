<?php

return [

    /**
     * These are all the skill names for the game
     */
     'skill_names' => [
         'Accuracy',
         'Dodge',
         'Looting'
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

      /**
       * These are the allowed artifact properties:
       */
       'artifact_properties' => [
           [
               'name' => 'Keaxes Vice',
               'base_damage_mod' => 5,
               'description' => 'Keaxes Vice packs a punch, crippling the enemy with its damage.',
           ],
           [
               'name' => 'Droths Dexterity Guide',
               'base_damage_mod' => 0,
               'description' => 'Keaxes Vice packs a punch, crippling the enemy with its damage.',
           ],
       ]
];
