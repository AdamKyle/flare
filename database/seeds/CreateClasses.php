<?php

namespace Database\Seeders;

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
                'str_mod'      => 5,
                'dur_mod'      => 0,
                'dex_mod'      => 0,
                'chr_mod'      => 0,
                'int_mod'      => 0,
                'accuracy_mod' => 0.01,
                'dodge_mod'    => 0,
                'deffense_mod' => 0.05,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Vampire',
                'damage_stat'  => 'dur',
                'str_mod'      => 0,
                'dur_mod'      => 5,
                'dex_mod'      => 0,
                'chr_mod'      => 0,
                'int_mod'      => 0,
                'accuracy_mod' => 0.03,
                'dodge_mod'    => 0.04,
                'deffense_mod' => 0.03,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Ranger',
                'damage_stat'  => 'dex',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 5,
                'chr_mod'      => 0,
                'int_mod'      => 1,
                'accuracy_mod' => 0.05,
                'dodge_mod'    => 0.03,
                'deffense_mod' => 0,
                'looting_mod'  => 0.05,
            ],
            [
                'name'         => 'Prophet',
                'damage_stat'  => 'chr',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 0,
                'chr_mod'      => 5,
                'int_mod'      => 1,
                'accuracy_mod' => 0,
                'dodge_mod'    => 0.05,
                'deffense_mod' => 0,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Heretic',
                'damage_stat'  => 'int',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 0,
                'chr_mod'      => 3,
                'int_mod'      => 5,
                'accuracy_mod' => 0,
                'dodge_mod'    => 0.05,
                'deffense_mod' => 0,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Thief',
                'damage_stat'  => 'dex',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 5,
                'chr_mod'      => 0,
                'int_mod'      => 1,
                'accuracy_mod' => 0.05,
                'dodge_mod'    => 0.05,
                'deffense_mod' => 0,
                'looting_mod'  => 0.05,
            ],
        ]);
    }
}
