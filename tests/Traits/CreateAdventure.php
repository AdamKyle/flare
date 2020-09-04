<?php

namespace Tests\Traits;

use App\Flare\Models\Adventure;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;
use Tests\Traits\CreateItem;
use Tests\traits\CreateMonster;

trait CreateAdventure {

    use CreateItem;

    public function createNewAdventure(Monster $monster = null): Adventure {

        $adventure = factory(Adventure::class)->create([
            'name'             => 'Sample',
            'description'      => 'Sample description',
            'reward_item_id'   => $this->createItem([
                'name'        => 'Item Name',
                'type'        => 'weapon',
                'base_damage' => 1,
                'cost'        => 1,
            ]),
            'levels'           => 1,
            'time_per_level'   => 1,
            'gold_rush_chance' => 0.10,
            'item_find_chance' => 0.10,
            'skill_exp_bonus'  => 0.10,
        ]);

        if (is_null($monster)) {
            $monster = $this->createMonsterForAdventure();
        }

        $adventure->monsters()->attach($monster);

        return $adventure;
    }

    public function createLog(
        Character $character, 
        Adventure $adventure, 
        bool $inProgress = false, 
        int $lastCompletedLevel = 1
    ): AdventureLog {

        return factory(AdventureLog::class)->create([
            'character_id'         => $character->id,
            'adventure_id'         => $adventure->id,
            'complete'             => false,
            'in_progress'          => $inProgress,
            'last_completed_level' => $lastCompletedLevel,
            'logs'                 => null,
        ]);
    }

    protected function createMonsterForAdventure(): Monster {
        $monster = factory(Monster::class)->create([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 1,
            'dur' => 2,
            'dex' => 4,
            'chr' => 1,
            'int' => 1,
            'ac' => 1,
            'gold' => 1,
            'max_level' => 10,
            'health_range' => '1-1',
            'attack_range' => '1-1',
            'drop_check' => 0.10,
        ]);

        foreach(config('game.skills') as $options) {
            $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($monster, $options);
        }

        $monster->skills()->insert($skills);

        return $monster->refresh();
    }
}
