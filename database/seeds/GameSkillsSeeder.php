<?php

namespace Database\Seeders;

use App\Flare\Models\GameSkill;
use Illuminate\Database\Seeder;

class GameSkillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GameSkill::insert([
            [
                'name'                               => 'Accuracy',
                'description'                        => 'Helps in Determining the accuracy of your attack.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => true,
                'max_level'                          => 100,
                'skill_bonus_per_level'              => 0.01
             ],
             [
                'name'                               => 'Dodge',
                'description'                        => 'Helps in Determining if you can dodge the attack.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => true,
                'max_level'                          => 100,
                'skill_bonus_per_level'              => 0.01
             ],
             [
                'name'                               => 'Looting',
                'description'                        => 'Determines if you get an item or not per fight.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => true,
                'max_level'                          => 100,
                'skill_bonus_per_level'              => 0.01
             ],
             [
                'name'                               => 'Weapon Crafting',
                'description'                        => 'A skill used in crafting weapons.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => false,
                'max_level'                          => 400,
                'skill_bonus_per_level'              => 0.0025
             ],
             [
                'name'                               => 'Armour Crafting',
                'description'                        => 'A skill used in crafting armour.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => false,
                'max_level'                          => 400,
                'skill_bonus_per_level'              => 0.0025
             ],
             [
                'name'                               => 'Spell Crafting',
                'description'                        => 'A skill used in crafting spells.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => false,
                'max_level'                          => 400,
                'skill_bonus_per_level'              => 0.0025
             ],
             [
                'name'                               => 'Ring Crafting',
                'description'                        => 'A skill used in crafting rings.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => false,
                'max_level'                          => 400,
                'skill_bonus_per_level'              => 0.0025
             ],
             [
                'name'                               => 'Artifact Crafting',
                'description'                        => 'A skill used in crafting artifact.',
                'base_damage_mod_bonus_per_level'    => 0.0,
                'base_healing_mod_bonus_per_level'   => 0.0,
                'base_ac_mod_bonus_per_level'        => 0.0,
                'fight_time_out_mod_bonus_per_level' => 0.0,
                'move_time_out_mod_bonus_per_level'  => 0.0,
                'can_train'                          => false,
                'max_level'                          => 400,
                'skill_bonus_per_level'              => 0.0025
             ],
             [
               'name'                               => 'Enchanting',
               'description'                        => 'A skill used in enchanting items.',
               'base_damage_mod_bonus_per_level'    => 0.0,
               'base_healing_mod_bonus_per_level'   => 0.0,
               'base_ac_mod_bonus_per_level'        => 0.0,
               'fight_time_out_mod_bonus_per_level' => 0.0,
               'move_time_out_mod_bonus_per_level'  => 0.0,
               'can_train'                          => false,
               'max_level'                          => 400,
               'skill_bonus_per_level'              => 0.0025
            ],
        ]);
    }
}
