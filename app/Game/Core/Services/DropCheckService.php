<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Maps\Values\LocationType;
use Exception;
use Facades\App\Game\Core\Chance\DropCheckCalculator;

class DropCheckService
{
    private BattleDrop $battleDrop;

    private Monster $monster;

    private ?Location $locationWithEffect = null;

    private ?Location $manualQuestItemLocation = null;

    private ?string $cachedLocationWithEffectKey = null;

    private ?Location $cachedLocationWithEffect = null;

    private ?string $cachedManualQuestItemLocationKey = null;

    private ?Location $cachedManualQuestItemLocation = null;

    private BuildMythicItem $buildMythicItem;

    private float $lootingChance = 0.0;

    private float $gameMapBonus = 0.0;

    private float $mythicItemDropBonus = 0.0;

    private float $questItemDropBonus = 0.0;

    public function __construct(
        BattleDrop $battleDrop,
        BuildMythicItem $buildMythicItem,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
    ) {
        $this->battleDrop = $battleDrop;
        $this->buildMythicItem = $buildMythicItem;
    }

    /**
     * Process the drop check.
     *
     *
     * @throws Exception
     */
    public function process(Character $character, Monster $monster, ?float $lootingChance = null, bool $questItemsOnly = false): array
    {
        $this->gameMapBonus = 0.0;

        $this->lootingChance = $lootingChance ?? $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster = $monster;

        $characterMap = $character->map;
        $gameMap = $characterMap->gameMap;

        if (! is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $resolvedAreaGemEffects = $this->characterAreaGemEffectService->resolveForCharacter($character);
        $this->gameMapBonus += $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::ITEM_DROP_CHANCE_INCREASE);
        $this->mythicItemDropBonus = $resolvedAreaGemEffects->rarityEffects()->mythic();
        $this->questItemDropBonus = $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE);

        $this->findLocationWithEffect($characterMap);
        $this->findManualQuestItemLocation($characterMap);

        $this->battleDrop = $this->battleDrop->setMonster($this->monster)
            ->setSpecialLocation($this->locationWithEffect)
            ->setManualQuestItemLocation($this->manualQuestItemLocation)
            ->setGameMapBonus($this->gameMapBonus)
            ->setQuestItemDropBonus($this->questItemDropBonus)
            ->setLootingChance($this->lootingChance)
            ->resetRewardTotals();

        $this->handleDropChance($character, $questItemsOnly);

        if (! $questItemsOnly && $monster->celestial_type === 1) {
            $this->handleMythicDrop($character, true);
        }

        if (is_null($this->locationWithEffect)) {
            return $this->battleDrop->rewardTotals();
        }

        if (is_null($this->locationWithEffect->type)) {
            return $this->battleDrop->rewardTotals();
        }

        $locationType = LocationType::from($this->locationWithEffect->type);

        if (! $questItemsOnly && $locationType->isPurgatoryDungeons() && $character->currentAutomations->isEmpty()) {
            $this->handleMythicDrop($character);
        }

        return $this->battleDrop->rewardTotals();
    }

    /**
     * Plan the drops for a batch of kills without persisting them.
     */
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

        $resolvedAreaGemEffects = $this->characterAreaGemEffectService->resolveForCharacter($character);
        $this->gameMapBonus += $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::ITEM_DROP_CHANCE_INCREASE);
        $this->mythicItemDropBonus = $resolvedAreaGemEffects->rarityEffects()->mythic();
        $this->questItemDropBonus = $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE);

        $this->findLocationWithEffect($characterMap);
        $this->findManualQuestItemLocation($characterMap);

        $this->battleDrop = $this->battleDrop->setMonster($this->monster)
            ->setSpecialLocation($this->locationWithEffect)
            ->setManualQuestItemLocation($this->manualQuestItemLocation)
            ->setGameMapBonus($this->gameMapBonus)
            ->setQuestItemDropBonus($this->questItemDropBonus)
            ->setLootingChance($this->lootingChance)
            ->resetRewardTotals();

        $plannedDrops = [];
        $plannedQuestItemIds = [];

        for ($killIndex = 0; $killIndex < $killCount; $killIndex++) {
            $normalDrop = $this->battleDrop->handleDrop($character, $this->canHaveDrop($character), true);

            if (! is_null($normalDrop)) {
                $this->appendPlannedDrop($plannedDrops, $plannedQuestItemIds, $normalDrop, 'monster_drop');
            }

            $monsterQuestDrop = $this->battleDrop->handleMonsterQuestDrop($character, true);

            if (! is_null($monsterQuestDrop)) {
                $this->appendPlannedDrop($plannedDrops, $plannedQuestItemIds, $monsterQuestDrop, 'monster_quest_drop');
            }

            $delveQuestDrop = $this->battleDrop->planDelveLocationQuestItem($character);

            if (! is_null($delveQuestDrop)) {
                $this->appendPlannedDrop($plannedDrops, $plannedQuestItemIds, $delveQuestDrop, 'delve_location_quest_drop');
            }

            if (! is_null($this->manualQuestItemLocation)) {
                $specialLocationQuestDrop = $this->battleDrop->planSpecialLocationQuestItem($character);

                if (! is_null($specialLocationQuestDrop)) {
                    $this->appendPlannedDrop($plannedDrops, $plannedQuestItemIds, $specialLocationQuestDrop, 'special_location_quest_drop');
                }
            }
        }

        if ($monster->celestial_type === 1 && $this->canHaveMythic(true)) {
            $plannedDrops[] = [
                'item_id' => $this->buildMythicItem->fetchMythicItem($character)->id,
                'is_mythic' => true,
                'source' => 'king_celestial_mythic',
            ];
        }

        if (! is_null($this->locationWithEffect) && ! is_null($this->locationWithEffect->type)) {
            $locationType = LocationType::from($this->locationWithEffect->type);

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
     * Append a planned drop, skipping quest items already planned in this batch.
     */
    private function appendPlannedDrop(array &$plannedDrops, array &$plannedQuestItemIds, Item $item, string $source): void
    {
        if ($item->type === 'quest') {
            if (in_array($item->id, $plannedQuestItemIds, true)) {
                return;
            }

            $plannedQuestItemIds[] = $item->id;
        }

        $plannedDrops[] = [
            'item_id' => $item->id,
            'is_mythic' => false,
            'source' => $source,
        ];
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
    private function handleDropChance(Character $character, bool $questItemsOnly = false): void
    {
        if (! $questItemsOnly) {
            $canGetDrop = $this->canHaveDrop($character);
            $this->battleDrop->handleDrop($character, $canGetDrop);
        }

        $this->battleDrop->handleMonsterQuestDrop($character);

        $this->battleDrop->handleDelveLocationQuestItems($character);

        if (! is_null($this->manualQuestItemLocation)) {
            $this->battleDrop->handleSpecialLocationQuestItem($character);
        }
    }

    /**
     * Are we at a location with an effect (special location)?
     */
    private function findLocationWithEffect(Map $map): void
    {
        $cacheKey = $this->makeLocationWithEffectCacheKey($map);

        if ($this->cachedLocationWithEffectKey === $cacheKey) {
            $this->locationWithEffect = $this->cachedLocationWithEffect;

            return;
        }

        $this->locationWithEffect = Location::whereNotNull('type')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->first();

        $this->cachedLocationWithEffectKey = $cacheKey;
        $this->cachedLocationWithEffect = $this->locationWithEffect;
    }

    private function findManualQuestItemLocation(Map $map): void
    {
        $cacheKey = $this->makeLocationWithEffectCacheKey($map);

        if ($this->cachedManualQuestItemLocationKey === $cacheKey) {
            $this->manualQuestItemLocation = $this->cachedManualQuestItemLocation;

            return;
        }

        $this->manualQuestItemLocation = Location::whereNotNull('type')
            ->whereIn('type', LocationType::manualQuestDropValues())
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->dropsQuestItems()
            ->first();

        $this->cachedManualQuestItemLocationKey = $cacheKey;
        $this->cachedManualQuestItemLocation = $this->manualQuestItemLocation;
    }

    /**
     * Build a cache key for determining if we need to re-query the location effect.
     */
    private function makeLocationWithEffectCacheKey(Map $map): string
    {
        return $map->game_map_id.':'.$map->character_position_x.':'.$map->character_position_y;
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

            return DropCheckCalculator::fetchDifficultItemChance($chance + $this->mythicItemDropBonus);
        }

        return DropCheckCalculator::fetchDifficultItemChance($this->mythicItemDropBonus);
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
