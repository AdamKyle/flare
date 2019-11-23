<?php

use Illuminate\Database\Seeder;
use App\Flare\Models\GameClass;

class CreateClasses extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GameClass::insert([
            [
                'name'         => 'Fighter',
                'damage_stat'  => 'str',
                'str_mod'      => 1,
                'dur_mod'      => 1,
                'dex_mod'      => 0,
                'chr_mod'      => 0,
                'int_mod'      => 0,
                'accuracy_mod' => 2,
                'dodge_mod'    => 2,
                'deffense_mod' => 3,
            ],
            [
                'name'         => 'Vampire',
                'damage_stat'  => 'dur',
                'str_mod'      => 0,
                'dur_mod'      => 3,
                'dex_mod'      => 0,
                'chr_mod'      => 0,
                'int_mod'      => 0,
                'accuracy_mod' => 2,
                'dodge_mod'    => 4,
                'deffense_mod' => 0,
            ],
            [
                'name'         => 'Ranger',
                'damage_stat'  => 'dex',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 3,
                'chr_mod'      => 0,
                'int_mod'      => 3,
                'accuracy_mod' => 5,
                'dodge_mod'    => 5,
                'deffense_mod' => 0,
            ],
            [
                'name'         => 'Prophet',
                'damage_stat'  => 'chr',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 0,
                'chr_mod'      => 3,
                'int_mod'      => 1,
                'accuracy_mod' => 1,
                'dodge_mod'    => 1,
                'deffense_mod' => 0,
            ],
            [
                'name'         => 'Heretic',
                'damage_stat'  => 'int',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 0,
                'chr_mod'      => 0,
                'int_mod'      => 5,
                'accuracy_mod' => 3,
                'dodge_mod'    => 0,
                'deffense_mod' => 0,
            ],
        ]);
    }
}
