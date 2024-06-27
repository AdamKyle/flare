<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\WeeklyMonsterFight;
use App\Flare\Values\LocationType;
use App\Game\BattleRewardProcessing\Handlers\LocationSpecialtyHandler;
use Exception;

class WeeklyBattleService {

    /**
     * @var LocationSpecialtyHandler $locationSpecialtyHandler
     */
    private LocationSpecialtyHandler $locationSpecialtyHandler;

    /**
     * @var array $validLocationTypes
     */
    private array $validLocationTypes = [
        LocationType::ALCHEMY_CHURCH,
    ];

    /**
     * @param LocationSpecialtyHandler $locationSpecialtyHandler
     */
    public function __construct(LocationSpecialtyHandler $locationSpecialtyHandler) {
        $this->locationSpecialtyHandler = $locationSpecialtyHandler;
    }

    /**
     * Handle creating or updating the weekly fights record when a character dies.
     *
     * @param Character $character
     * @param Monster $monster
     * @return void
     */
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

    /**
     * Handle creating or updating the weekly fights record when a monster dies.
     *
     * @param Character $character
     * @param Monster $monster
     * @return Character
     * @throws Exception
     */
    public function handleMonsterDeath(Character $character, Monster $monster): Character {
        if (!in_array($monster->only_for_location_type, $this->validLocationTypes)) {
            return $character;
        }

        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            $weeklyMonsterFight = $character->weeklyBattleFights()->create([
                'character_id' => $character->id,
                'monster_id'   => $monster->id,
                'monster_was_killed' => true,
            ]);


            return $this->handleReward($character, $monster, $weeklyMonsterFight);
        }

        $weeklyMonsterFight->update([
            'monster_was_killed' => true,
        ]);

        $weeklyMonsterFight = $weeklyMonsterFight->refresh();

        return $this->handleReward($character, $monster, $weeklyMonsterFight);
    }

    /**
     * Can we fight the monster?
     *
     * @param Character $character
     * @param Monster $monster
     * @return bool
     */
    public function canFightMonster(Character $character, Monster $monster): bool {
        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            return true;
        }

        return !$weeklyMonsterFight->monster_was_killed;
    }

    /**
     * Handle rewarding the player.
     *
     * @param Character $character
     * @param Monster $monster
     * @param WeeklyMonsterFight $weeklyMonsterFight
     * @return Character
     * @throws Exception
     */
    private function handleReward(Character $character, Monster $monster, WeeklyMonsterFight $weeklyMonsterFight): Character {

        $locationType = new LocationType($monster->only_for_location_type);

        if ($locationType->isAlchemyChurch()) {
            $this->locationSpecialtyHandler->handleMonsterFromSpecialLocation($character, $weeklyMonsterFight);
        }

        if ($locationType->isLordsStrongHold()) {
            $this->locationSpecialtyHandler->handleMonsterFromSpecialLocation($character, $weeklyMonsterFight, false);
        }

        return $character->refresh();
    }
}
