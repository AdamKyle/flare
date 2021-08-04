<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Flare\Models\GameRace;

class CreateRaces extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GameRace::insert([
            [
                'name'         => 'Human',
                'str_mod'      => 1,
                'dur_mod'      => 1,
                'dex_mod'      => 1,
                'chr_mod'      => 1,
                'int_mod'      => 1,
                'accuracy_mod' => 0.03,
                'dodge_mod'    => 0.02,
                'defense_mod' => 0.01,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'High Elf',
                'str_mod'      => 0,
                'dur_mod'      => 1,
                'dex_mod'      => 2,
                'chr_mod'      => 2,
                'int_mod'      => 3,
                'accuracy_mod' => 0.10,
                'dodge_mod'    => 0.05,
                'defense_mod' => 0,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Dark Dwarf',
                'str_mod'      => 3,
                'dur_mod'      => 2,
                'dex_mod'      => 2,
                'chr_mod'      => 1,
                'int_mod'      => 0,
                'accuracy_mod' => 0,
                'dodge_mod'    => 0,
                'defense_mod' => 0.05,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Centaur',
                'str_mod'      => 3,
                'dur_mod'      => 2,
                'dex_mod'      => 2,
                'chr_mod'      => 0,
                'int_mod'      => 0,
                'accuracy_mod' => 0.07,
                'dodge_mod'    => 0,
                'defense_mod' => 0.02,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Dryad',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 2,
                'chr_mod'      => 2,
                'int_mod'      => 3,
                'accuracy_mod' => 0.5,
                'dodge_mod'    => 0.5,
                'defense_mod' => 0,
                'looting_mod'  => 0,
            ],
            [
                'name'         => 'Orc',
                'str_mod'      => 2,
                'dur_mod'      => 0,
                'dex_mod'      => 3,
                'chr_mod'      => 0,
                'int_mod'      => 0,
                'accuracy_mod' => 0,
                'dodge_mod'    => 0,
                'defense_mod' => 0,
                'looting_mod'  => 0.02,
            ]
        ]);
    }
}
