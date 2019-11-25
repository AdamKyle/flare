<?php

use Illuminate\Database\Seeder;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Flare\Values\BaseSkillValue;

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
                'health_range' => '8-20',
                'attack_range' => '2-8',
            ]
        ]);

        $skills     = [];

        foreach(Monster::all() as $monster) {
            foreach(config('game.skill_names') as $name) {
                $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($monster, $name);
            }

            $monster->skills()->insert($skills);
        }
    }
}
