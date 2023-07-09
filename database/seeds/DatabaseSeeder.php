<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesAndPermissions::class,
            CreateRaces::class,
            CreateClasses::class,
            GameSkillsSeeder::class,
            CreatePortLocationsSeeder::class,
            SetMaxLevelSeeder::class,
            SetupRankFightsSeeder::class,
        ]);
    }
}
