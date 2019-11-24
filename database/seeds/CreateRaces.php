<?php

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
                'dur_mod'      => 0,
                'dex_mod'      => 1,
                'chr_mod'      => 1,
                'int_mod'      => 1,
                'accuracy_mod' => 1,
                'dodge_mod'    => 2,
                'deffense_mod' => 0,
            ],
            [
                'name'         => 'High Elf',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 2,
                'chr_mod'      => 2,
                'int_mod'      => 3,
                'accuracy_mod' => 3,
                'dodge_mod'    => 3,
                'deffense_mod' => 0,
            ],
            [
                'name'         => 'Dark Dwarf',
                'str_mod'      => 3,
                'dur_mod'      => 3,
                'dex_mod'      => 0,
                'chr_mod'      => 3,
                'int_mod'      => 0,
                'accuracy_mod' => 0,
                'dodge_mod'    => 0,
                'deffense_mod' => 2,
            ],
            [
                'name'         => 'Centaur',
                'str_mod'      => 3,
                'dur_mod'      => 3,
                'dex_mod'      => 2,
                'chr_mod'      => 0,
                'int_mod'      => 0,
                'accuracy_mod' => 3,
                'dodge_mod'    => 5,
                'deffense_mod' => 0,
            ],
            [
                'name'         => 'Dryad',
                'str_mod'      => 0,
                'dur_mod'      => 0,
                'dex_mod'      => 3,
                'chr_mod'      => 3,
                'int_mod'      => 5,
                'accuracy_mod' => 3,
                'dodge_mod'    => 5,
                'deffense_mod' => 0,
            ],
            [
                'name'         => 'Orc',
                'str_mod'      => 1,
                'dur_mod'      => 1,
                'dex_mod'      => 1,
                'chr_mod'      => 2,
                'int_mod'      => 0,
                'accuracy_mod' => 2,
                'dodge_mod'    => 2,
                'deffense_mod' => 0,
            ]
        ]);
    }
}
