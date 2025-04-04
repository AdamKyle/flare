<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\WeeklyMonsterFight;
use App\Flare\Values\LocationType;
use App\Game\BattleRewardProcessing\Handlers\LocationSpecialtyHandler;
use Exception;

class WeeklyBattleService
{
    private LocationSpecialtyHandler $locationSpecialtyHandler;

    private array $validLocationTypes = [
        LocationType::ALCHEMY_CHURCH,
        LocationType::LORDS_STRONG_HOLD,
        LocationType::BROKEN_ANVIL,
        LocationType::TWSITED_MAIDENS_DUNGEONS
    ];

    public function __construct(LocationSpecialtyHandler $locationSpecialtyHandler)
    {
        $this->locationSpecialtyHandler = $locationSpecialtyHandler;
    }

    /**
     * Handle creating or updating the weekly fights record when a character dies.
     */
    public function handleCharacterDeath(Character $character, Monster $monster): void
    {

        if (! in_array($monster->only_for_location_type, $this->validLocationTypes)) {
            return;
        }

        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            $character->weeklyBattleFights()->create([
                'character_id' => $character->id,
                'monster_id' => $monster->id,
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
     * @throws Exception
     */
    public function handleMonsterDeath(Character $character, Monster $monster): Character
    {
        if (! in_array($monster->only_for_location_type, $this->validLocationTypes)) {
            return $character;
        }

        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            $weeklyMonsterFight = $character->weeklyBattleFights()->create([
                'character_id' => $character->id,
                'monster_id' => $monster->id,
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
     */
    public function canFightMonster(Character $character, Monster $monster): bool
    {
        $weeklyMonsterFight = $character->weeklyBattleFights()->where('monster_id', $monster->id)->first();

        if (is_null($weeklyMonsterFight)) {
            return true;
        }

        return ! $weeklyMonsterFight->monster_was_killed;
    }

    /**
     * Handle rewarding the player.
     *
     * @throws Exception
     */
    private function handleReward(Character $character, Monster $monster, WeeklyMonsterFight $weeklyMonsterFight): Character
    {

        $locationType = new LocationType($monster->only_for_location_type);

        if ($locationType->isAlchemyChurch() || $locationType->isCaveOfMemories()) {
            $this->locationSpecialtyHandler->handleMonsterFromSpecialLocation($character, $weeklyMonsterFight);
        }

        if ($locationType->isLordsStrongHold() || $locationType->isHellsBrokenAnvil() || $locationType->isTwistedMaidensDungeons()) {
            $this->locationSpecialtyHandler->handleMonsterFromSpecialLocation($character, $weeklyMonsterFight, false);
        }

        return $character->refresh();
    }
}
