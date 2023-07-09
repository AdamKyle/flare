<?php

namespace Database\Seeders;

use App\Flare\Models\MaxLevelConfiguration;
use Illuminate\Database\Seeder;

class SetupRankFightsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MaxLevelConfiguration::create([
            'max_level'      => 5000,
            'half_way'       => 2500,
            'three_quarters' => ceil(5000 * .75),
            'last_leg'       => 4000
        ]);
    }
}