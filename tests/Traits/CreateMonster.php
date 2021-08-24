<?php

namespace Tests\Traits;

use App\Flare\Models\GameMap;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;

trait CreateMonster {

    use CreateGameSkill, CreateGameMap;

    public function createMonster(array $options = []): Monster {
        if (empty($options) || !isset($options['game_map_id'])) {

            $maps = GameMap::all();

            if ($maps->isEmpty()) {
                $map = $this->createGameMap();
            } else {
                $map = $maps->first();
            }

            $options['game_map_id'] = $map->id;
        }

        $monster     = Monster::factory()->create($options);
        $gameSkills  = $this->fetchSkills();
        $skills      = [];

        foreach ($gameSkills as $gameSkill) {
            if ($gameSkill->can_train) {
                $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($monster, $gameSkill);
            }
        }

        $monster->skills()->insert($skills);

        return $monster->refresh();
    }

    protected function fetchSkills(): Collection {
        $skills = GameSkill::whereNull('game_class_id')->get();

        if ($skills->isEmpty()) {
            $this->createGameSkill(['name' => 'Accuracy']);
            $this->createGameSkill(['name' => 'Dodge']);
            $this->createGameSkill(['name' => 'Looting']);

            return GameSkill::whereNull('game_class_id')->get();
        }

        return $skills;
    }
}
