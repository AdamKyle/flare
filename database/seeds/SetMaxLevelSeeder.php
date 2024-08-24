<?php

namespace Database\Seeders;

use App\Flare\Models\RankFight;
use Illuminate\Database\Seeder;

class SetMaxLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RankFight::create([
            'current_rank' => 10,
        ]);
    }
}
