<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Flare\Models\ItemAffix;

class CreateAffixes extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ItemAffix::insert([
            [
                'name'                 => 'Queens Blessing',
                'base_damage_mod'      => '0.05',
                'type'                 => 'suffix',
                'description'          => 'The Queens Blessing brings balance to all in her name.',
                'base_healing_mod'     => '0.02',
                'str_mod'              => '0.02',
                'dur_mod'              => '0.02',
                'dex_mod'              => '0.02',
                'chr_mod'              => '0.02',
                'int_mod'              => '0.02',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 500,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Lords Blessing',
                'base_damage_mod'      => '0.05',
                'type'                 => 'suffix',
                'description'          => 'The Lords Blessing brings balance to all in his name.',
                'base_healing_mod'     => '0.04',
                'str_mod'              => '0.04',
                'dur_mod'              => '0.04',
                'dex_mod'              => '0.04',
                'chr_mod'              => '0.04',
                'int_mod'              => '0.04',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 500,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Devils Sigil',
                'base_damage_mod'      => '0.10',
                'type'                 => 'suffix',
                'description'          => 'A sigil carved into items to give it the (weaken) power of the devil.',
                'base_healing_mod'     => '0.05',
                'str_mod'              => '0.00',
                'dur_mod'              => '0.00',
                'dex_mod'              => '0.00',
                'chr_mod'              => '0.00',
                'int_mod'              => '0.10',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 1000,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Vampric Blood',
                'base_damage_mod'      => '0.10',
                'type'                 => 'suffix',
                'description'          => 'The blood of vampires dripped on the item gives a rush of magical power.',
                'base_healing_mod'     => '0.10',
                'str_mod'              => '0.00',
                'dur_mod'              => '0.10',
                'dex_mod'              => '0.00',
                'chr_mod'              => '0.00',
                'int_mod'              => '0.00',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 1000,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Hunters Trap',
                'base_damage_mod'      => '0.10',
                'type'                 => 'suffix',
                'description'          => 'Discovered by Rangers, this trap placed on items helps to deal even more damage.',
                'base_healing_mod'     => '0.00',
                'str_mod'              => '0.00',
                'dur_mod'              => '0.00',
                'dex_mod'              => '0.10',
                'chr_mod'              => '0.00',
                'int_mod'              => '0.00',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 1000,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
        ]);
    }
}
