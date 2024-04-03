<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Values\LocationType;
use App\Game\BattleRewardProcessing\Handlers\LocationSpecialtyHandler;

class WeeklyBattleService {

    private LocationSpecialtyHandler $locationSpecialtyHandler;

    private array $validLocationTypes = [
        LocationType::ALCHEMY_CHURCH,
    ];

    public function __construct(LocationSpecialtyHandler $locationSpecialtyHandler) {
        $this->locationSpecialtyHandler = $locationSpecialtyHandler;
    }

    public function handleCharacterDeath(Character $character, Monster $monster): void {

        if (!in_array($monster->only_for_location_type, $this->validLocationTypes)) {
            return;
        }

        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            $character->weeklyBattleFights()->create([
                'character_id' => $character->id,
                'monster_id'   => $monster->id,
                'character_deaths' => 1,
            ]);

            return;
        }

        $weeklyMonsterFight->update([
            'character_deaths' => $weeklyMonsterFight->character_deaths + 1,
        ]);
    }

    public function handleMonsterDeath(Character $character, Monster $monster): void {
        if (!in_array($monster->only_for_location_type, $this->validLocationTypes)) {
            return;
        }

        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            $character->weeklyBattleFights()->create([
                'character_id' => $character->id,
                'monster_id'   => $monster->id,
                'monster_was_killed' => true,
            ]);

            return;
        }

        $weeklyMonsterFight->update([
            'monster_was_killed' => true,
        ]);
    }

    public function canFightMonster(Character $character, Monster $monster): bool {
        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            return true;
        }

        return !$weeklyMonsterFight->monster_was_killed;
    }

    public function handleWeeklyBattle(Character $character, Monster $monster): Character {

        if (!in_array($monster->only_for_location_type, $this->validLocationTypes)) {
            return $character;
        }

        $locationType = new LocationType($monster->only_for_location_type);

        if ($locationType->isAlchemyChurch()) {
            $this->locationSpecialtyHandler->handleMonsterFromSpecialLocation($character, $monster);
        }


        return $character->refresh();
    }
}
