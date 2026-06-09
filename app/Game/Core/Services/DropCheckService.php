<?php

namespace App\Game\Core\Services;

use App\Flare\Items\Builders\BuildMythicItem;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Battle\Services\BattleDrop;
use Exception;
use Facades\App\Flare\Calculators\DropCheckCalculator;

class DropCheckService
{
    private BattleDrop $battleDrop;

    private Monster $monster;

    private BuildMythicItem $buildMythicItem;

    private float $lootingChance = 0.0;

    private float $gameMapBonus = 0.0;

    public function __construct(BattleDrop $battleDrop, BuildMythicItem $buildMythicItem)
    {
        $this->battleDrop = $battleDrop;
        $this->buildMythicItem = $buildMythicItem;
    }

    /**
     * Process the drop check.
     *
     * @throws Exception
     */
    public function process(Character $character, Monster $monster, ?float $lootingChance = null): array
    {
        $this->gameMapBonus = 0.0;

        $this->lootingChance = $lootingChance ?? $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster = $monster;

        $characterMap = $character->map;
        $gameMap = $characterMap->gameMap;

        if (! is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $this->battleDrop = $this->battleDrop->setMonster($this->monster)
            ->setSpecialLocation(null)
            ->setGameMapBonus($this->gameMapBonus)
            ->setLootingChance($this->lootingChance)
            ->resetRewardTotals();

        $this->handleDropChance($character);

        $this->handleMythicDrop($character, true);

        return $this->battleDrop->rewardTotals();
    }

    /**
     * See if the player can have a mythic drop.
     *
     *
     * @throws Exception
     */
    private function handleMythicDrop(Character $character, bool $useLootingChance = false): void
    {
        $canGetDrop = $this->canHaveMythic($useLootingChance);

        if ($canGetDrop) {
            $mythic = $this->buildMythicItem->fetchMythicItem($character);

            $this->battleDrop->giveMythicItem($character, $mythic);
        }
    }

    /**
     * Handles the drops themselves based on chance.
     *
     *
     * @throws Exception
     */
    private function handleDropChance(Character $character): void
    {
        $canGetDrop = $this->canHaveDrop($character);

        $this->battleDrop->handleDrop($character, $canGetDrop);

        $this->battleDrop->handleMonsterQuestDrop($character);

        $this->battleDrop->handleDelveLocationQuestItems($character);
    }

    /**
     * Can we get the mythic item?
     */
    private function canHaveMythic(bool $useLooting = false): bool
    {
        $chance = $this->lootingChance;

        if ($useLooting) {

            if ($chance > 0.15) {
                $chance = 0.15;
            }

            return DropCheckCalculator::fetchDifficultItemChance($chance);
        }

        return DropCheckCalculator::fetchDifficultItemChance();
    }

    /**
     * Can we have the drop?
     *
     * @throws Exception
     */
    private function canHaveDrop(Character $character): bool
    {
        return DropCheckCalculator::fetchDropCheckChance($this->monster, $character->level, $this->lootingChance, $this->gameMapBonus);
    }
}
