<?php

use Illuminate\Database\Seeder;
use App\Flare\Models\Monster;

class CreateMonstersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Monster::insert([
            [
                'name'         => 'Goblin',
                'damage_stat'  => 'str',
                'xp'           => 10,
                'str'          => 1,
                'dur'          => 6,
                'dex'          => 7,
                'chr'          => 8,
                'int'          => 8,
                'ac'           => 6,
                'health_range' => '1-8',
                'attack_range' => '1-6',
            ]
        ]);
    }
}
