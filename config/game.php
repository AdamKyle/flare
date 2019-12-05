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
      ],

      /**
       * These are the allowed artifact properties:
       */
       'artifact_properties' => [
           [
               'name' => 'Keaxes Vice',
               'base_damage_mod' => 5,
               'type' => 'suffix',
               'description' => 'Keaxes Vice packs a punch, crippling the enemy with its damage.',
           ],
       ]
];
