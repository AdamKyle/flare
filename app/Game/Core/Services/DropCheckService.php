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

    public function planDrops(Character $character, Monster $monster, int $killCount = 1, ?float $lootingChance = null): array
    {
        $this->gameMapBonus = 0.0;
        $this->lootingChance = $lootingChance ?? $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster = $monster;

        $characterMap = $character->map;
        $gameMap = $characterMap->gameMap;

        if (! is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $this->findLocationWithEffect($characterMap);

        $this->battleDrop = $this->battleDrop->setMonster($this->monster)
            ->setSpecialLocation($this->locationWithEffect)
            ->setGameMapBonus($this->gameMapBonus)
            ->setLootingChance($this->lootingChance)
            ->resetRewardTotals();

        $plannedDrops = [];

        for ($killIndex = 0; $killIndex < $killCount; $killIndex++) {
            $normalDrop = $this->battleDrop->handleDrop($character, $this->canHaveDrop($character), true);

            if (! is_null($normalDrop)) {
                $plannedDrops[] = [
                    'item_id' => $normalDrop->id,
                    'is_mythic' => false,
                    'source' => 'monster_drop',
                ];
            }

            $monsterQuestDrop = $this->battleDrop->handleMonsterQuestDrop($character, true);

            if (! is_null($monsterQuestDrop)) {
                $plannedDrops[] = [
                    'item_id' => $monsterQuestDrop->id,
                    'is_mythic' => false,
                    'source' => 'monster_quest_drop',
                ];
            }

            $delveQuestDrop = $this->battleDrop->planDelveLocationQuestItem($character);

            if (! is_null($delveQuestDrop)) {
                $plannedDrops[] = [
                    'item_id' => $delveQuestDrop->id,
                    'is_mythic' => false,
                    'source' => 'delve_location_quest_drop',
                ];
            }

            if (! is_null($this->locationWithEffect)) {
                $specialLocationQuestDrop = $this->battleDrop->planSpecialLocationQuestItem($character);

                if (! is_null($specialLocationQuestDrop)) {
                    $plannedDrops[] = [
                        'item_id' => $specialLocationQuestDrop->id,
                        'is_mythic' => false,
                        'source' => 'special_location_quest_drop',
                    ];
                }
            }
        }

        if ($monster->celestial_type === CelestialType::KING_CELESTIAL && $this->canHaveMythic(true)) {
            $plannedDrops[] = [
                'item_id' => $this->buildMythicItem->fetchMythicItem($character)->id,
                'is_mythic' => true,
                'source' => 'king_celestial_mythic',
            ];
        }

        if (! is_null($this->locationWithEffect) && ! is_null($this->locationWithEffect->type)) {
            $locationType = new LocationType($this->locationWithEffect->type);

            if ($locationType->isPurgatoryDungeons() && $character->currentAutomations->isEmpty() && $this->canHaveMythic()) {
                $plannedDrops[] = [
                    'item_id' => $this->buildMythicItem->fetchMythicItem($character)->id,
                    'is_mythic' => true,
                    'source' => 'purgatory_dungeon_mythic',
                ];
            }
        }

        return [
            'kill_count' => $killCount,
            'looting_chance' => $this->lootingChance,
            'game_map_bonus' => $this->gameMapBonus,
            'location_with_effect_id' => $this->locationWithEffect?->id,
            'drops' => $plannedDrops,
        ];
    }

    public function applyPlannedDrops(Character $character, Monster $monster, array $plan): array
    {
        $this->monster = $monster;
        $this->battleDrop = $this->battleDrop->setMonster($monster)
            ->setSpecialLocation(null)
            ->setGameMapBonus((float) ($plan['game_map_bonus'] ?? 0.0))
            ->setLootingChance((float) ($plan['looting_chance'] ?? 0.0))
            ->resetRewardTotals();

        foreach ($plan['drops'] ?? [] as $drop) {
            $this->battleDrop->applyPlannedItem($character, (int) $drop['item_id'], (bool) ($drop['is_mythic'] ?? false));
        }

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
