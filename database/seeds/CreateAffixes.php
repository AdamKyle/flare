<?php

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
                'base_damage_mod'      => '0.02',
                'type'                 => 'suffix',
                'description'          => 'The queen has blessed this item.',
                'base_healing_mod'     => null,
                'str_mod'              => '0.05',
                'dur_mod'              => '0.01',
                'dex_mod'              => '0.05',
                'chr_mod'              => '0.01',
                'int_mod'              => '0.01',
                'ac_mod'               => '0.05',
                'skill_name'           => null,
                'skill_training_bonus' => null,
            ],
            [
                'name'                 => 'Lords Blessing',
                'base_damage_mod'      => '0.05',
                'type'                 => 'prefix',
                'description'          => 'A prayer has been answered in this item.',
                'base_healing_mod'     => '0.05',
                'str_mod'              => '0.01',
                'dur_mod'              => '0.01',
                'dex_mod'              => '0.01',
                'chr_mod'              => '0.05',
                'int_mod'              => '0.01',
                'ac_mod'               => '0.01',
                'skill_name'           => null,
                'skill_training_bonus' => null,
            ],
            [
                'name'                 => 'Demonic Sigil',
                'base_damage_mod'      => '0.05',
                'type'                 => 'suffix',
                'description'          => 'A demons sigil has been used on this item.',
                'base_healing_mod'     => '0.01',
                'str_mod'              => '0.01',
                'dur_mod'              => '0.01',
                'dex_mod'              => '0.01',
                'chr_mod'              => '0.01',
                'int_mod'              => '0.05',
                'ac_mod'               => '0.01',
                'skill_name'           => null,
                'skill_training_bonus' => null,
            ],
            [
                'name'                 => 'Vampric Blood',
                'base_damage_mod'      => '0.03',
                'type'                 => 'prefix',
                'description'          => 'Bathed in the blood of victims, this weapon radiates with power.',
                'base_healing_mod'     => '0.01',
                'str_mod'              => '0.01',
                'dur_mod'              => '0.05',
                'dex_mod'              => '0.01',
                'chr_mod'              => '0.01',
                'int_mod'              => '0.01',
                'ac_mod'               => '0.01',
                'skill_name'           => '0.01',
                'skill_training_bonus' => '0.01',
            ],
            [
                'name'                 => 'Hunters Trap',
                'base_damage_mod'      => '0.02',
                'type'                 => 'suffix',
                'description'          => 'Found in the middle of a hunters trap, this item helps with focus and determination in Rangers.',
                'base_healing_mod'     => '0.01',
                'str_mod'              => '0.01',
                'dur_mod'              => '0.01',
                'dex_mod'              => '0.05',
                'chr_mod'              => '0.01',
                'int_mod'              => '0.01',
                'ac_mod'               => '0.01',
                'skill_name'           => null,
                'skill_training_bonus' => null,
            ],
        ]);
    }
}
