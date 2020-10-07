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
                'description'          => 'The queen has blessed this item.',
                'base_healing_mod'     => null,
                'str_mod'              => '0.25',
                'dur_mod'              => '0.00',
                'dex_mod'              => '0.25',
                'chr_mod'              => '0.00',
                'int_mod'              => '0.00',
                'ac_mod'               => '0.25',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 500,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Lords Blessing',
                'base_damage_mod'      => '0.05',
                'type'                 => 'prefix',
                'description'          => 'A prayer has been answered in this item.',
                'base_healing_mod'     => '0.50',
                'str_mod'              => '0.00',
                'dur_mod'              => '0.00',
                'dex_mod'              => '0.00',
                'chr_mod'              => '0.25',
                'int_mod'              => '0.00',
                'ac_mod'               => '0.25',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 500,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Demonic Sigil',
                'base_damage_mod'      => '0.05',
                'type'                 => 'suffix',
                'description'          => 'A demons sigil has been used on this item.',
                'base_healing_mod'     => '0.00',
                'str_mod'              => '0.00',
                'dur_mod'              => '0.00',
                'dex_mod'              => '0.00',
                'chr_mod'              => '0.00',
                'int_mod'              => '0.25',
                'ac_mod'               => '0.00',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 500,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Vampric Blood',
                'base_damage_mod'      => '0.05',
                'type'                 => 'prefix',
                'description'          => 'Bathed in the blood of victims, this weapon radiates with power.',
                'base_healing_mod'     => '0.00',
                'str_mod'              => '0.00',
                'dur_mod'              => '0.25',
                'dex_mod'              => '0.00',
                'chr_mod'              => '0.00',
                'int_mod'              => '0.00',
                'ac_mod'               => '0.15',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 500,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
            [
                'name'                 => 'Hunters Trap',
                'base_damage_mod'      => '0.05',
                'type'                 => 'suffix',
                'description'          => 'Found in the middle of a hunters trap, this item helps with focus and determination in Rangers.',
                'base_healing_mod'     => '0.00',
                'str_mod'              => '0.00',
                'dur_mod'              => '0.00',
                'dex_mod'              => '0.25',
                'chr_mod'              => '0.00',
                'int_mod'              => '0.00',
                'ac_mod'               => '0.00',
                'skill_name'           => null,
                'skill_training_bonus' => null,
                'cost'                 => 500,
                'created_at'           => now(),
                'updated_at'           => null,
            ],
        ]);
    }
}
