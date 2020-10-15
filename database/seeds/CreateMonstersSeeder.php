<?php

namespace Database\Seeders;

use App\Flare\Models\GameSkill;
use Illuminate\Database\Seeder;
use App\Flare\Models\Monster;
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
                'xp'           => 5,
                'str'          => 1,
                'dur'          => 6,
                'dex'          => 7,
                'chr'          => 8,
                'int'          => 8,
                'ac'           => 6,
                'health_range' => '8-20',
                'attack_range' => '2-8',
                'gold'         => 25,
                'drop_check'   => 0.10,
            ]
        ]);

        foreach(Monster::all() as $monster) {
            foreach(GameSkill::where('specifically_assigned', false)->get() as $skill) {
                if ($skill->can_train) {
                    $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($monster, $skill);
                }
                
            }

            $monster->skills()->insert($skills);
        }
    }
}
