<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Gems\Progression\Contracts\CharacterAreaGemEffects;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Maps\Values\LocationType;
use Facades\App\Game\Core\Chance\DropCheckCalculator;
use Illuminate\Support\Facades\Log;

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

    /**
     * @param BattleDrop $battleDrop
     * @param BuildMythicItem $buildMythicItem
     * @param CharacterAreaGemEffects $characterAreaGemEffects
     */
    public function __construct(
        BattleDrop $battleDrop,
        BuildMythicItem $buildMythicItem,
        private readonly CharacterAreaGemEffects $characterAreaGemEffects,
    ) {
        $this->battleDrop = $battleDrop;
        $this->buildMythicItem = $buildMythicItem;
    }

    /**
     * Process the drop check.
     *
     * @param Character $character
     * @param Monster $monster
     * @param ?float $lootingChance
     * @param bool $questItemsOnly
     * @return array
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

        $resolvedAreaGemEffects = $this->characterAreaGemEffects->resolveForCharacterId($character->id);
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
     *
     * @param Character $character
     * @param Monster $monster
     * @param int $killCount
     * @param ?float $lootingChance
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return array
     */
    public function planDrops(Character $character, Monster $monster, int $killCount = 1, ?float $lootingChance = null, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): array
    {
        $startedAtNs = hrtime(true);

        $this->gameMapBonus = 0.0;
        $this->lootingChance = $lootingChance ?? $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster = $monster;

        $characterMap = $character->map;
        $gameMap = $characterMap->gameMap;

        if (! is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $resolvedAreaGemEffects ??= $this->characterAreaGemEffects->resolveForCharacterId($character->id);
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

        Log::channel('reward_processing')->info('Item drop planning summary.', [
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'kill_count' => $killCount,
            'planned_drop_count' => count($plannedDrops),
            'elapsed_ms' => intdiv(hrtime(true) - $startedAtNs, 1_000_000),
        ]);

        return [
            'kill_count' => $killCount,
            'looting_chance' => $this->lootingChance,
            'game_map_bonus' => $this->gameMapBonus,
            'location_with_effect_id' => $this->locationWithEffect?->id,
            'drops' => $plannedDrops,
        ];
    }

    /**
     * Apply a previously planned set of item drops to the Character.
     *
     * @param Character $character
     * @param Monster $monster
     * @param array $plan
     * @return array
     */
    public function applyPlannedDrops(Character $character, Monster $monster, array $plan): array
    {
        $startedAtNs = hrtime(true);

        $this->monster = $monster;
        $this->battleDrop = $this->battleDrop->setMonster($monster)
            ->setSpecialLocation(null)
            ->setGameMapBonus($plan['game_map_bonus'] ?? 0.0)
            ->setLootingChance($plan['looting_chance'] ?? 0.0)
            ->resetRewardTotals();

        $drops = $plan['drops'] ?? [];
        $itemIds = collect($drops)
            ->pluck('item_id')
            ->unique()
            ->values()
            ->all();

        $items = empty($itemIds)
            ? collect()
            : Item::whereIn('id', $itemIds)->get()->keyBy('id');

        $missingItemCount = 0;

        foreach ($drops as $drop) {
            $item = $items->get($drop['item_id']);

            if (is_null($item)) {
                $missingItemCount++;

                continue;
            }

            $this->battleDrop->applyPlannedItem(
                $character,
                $item,
                $drop['is_mythic'] ?? false,
            );
        }

        Log::channel('reward_processing')->info('Item drop application summary.', [
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'planned_drop_count' => count($drops),
            'loaded_unique_item_count' => $items->count(),
            'missing_item_count' => $missingItemCount,
            'elapsed_ms' => intdiv(hrtime(true) - $startedAtNs, 1_000_000),
        ]);

        return $this->battleDrop->rewardTotals();
    }

    /**
     * Append a planned drop, skipping quest items already planned in this batch.
     *
     * @param array $plannedDrops
     * @param array $plannedQuestItemIds
     * @param Item $item
     * @param string $source
     * @return void
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
     * @param Character $character
     * @param bool $useLootingChance
     * @return void
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
     * @param Character $character
     * @param bool $questItemsOnly
     * @return void
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
     *
     * @param Map $map
     * @return void
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

    /**
     * Resolve and cache whether the Character's current Location manually gates a quest item drop.
     *
     * @param Map $map
     * @return void
     */
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
     *
     * @param Map $map
     * @return string
     */
    private function makeLocationWithEffectCacheKey(Map $map): string
    {
        return $map->game_map_id.':'.$map->character_position_x.':'.$map->character_position_y;
    }

    /**
     * Can we get the mythic item?
     *
     * @param bool $useLooting
     * @return bool
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
     * @param Character $character
     * @return bool
     */
    private function canHaveDrop(Character $character): bool
    {
        return DropCheckCalculator::fetchDropCheckChance($this->monster, $character->level, $this->lootingChance, $this->gameMapBonus);
    }
}
