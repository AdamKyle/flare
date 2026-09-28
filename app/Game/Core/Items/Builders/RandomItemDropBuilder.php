<?php

namespace App\Game\Core\Items\Builders;

use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Services\ItemSocketRollService;

class RandomItemDropBuilder
{
    /**
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ChanceCalculator $chanceCalculator
     * @param ItemSocketRollService $itemSocketRollService
     */
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly ItemSocketRollService $itemSocketRollService,
    ) {}

    private int $socketCount = 0;

    private array $cachedAffixesByType = [];

    private array $cachedAffixesMaxLevelByType = [];

    /**
     * Generate a reusable affixed Item variant with its ordinary-drop socket roll.
     *
     * @param int $forLevel
     * @return Item|null
     */
    public function generateItem(int $forLevel): ?Item
    {
        $item = $this->getItem($forLevel);
        $this->socketCount = $this->itemSocketRollService->rollForOrdinaryDrop($item);
        $affixes = $this->getAffixes($forLevel);

        if (count($affixes) < 1) {
            return null;
        }

        $foundItem = $this->itemExists($item, $affixes);

        if (! is_null($foundItem)) {
            return $foundItem;
        }

        return $this->createItem($item, $affixes);
    }

    /**
     * Return a random eligible base Item without attached affixes.
     *
     * @param int $level
     * @return Item
     */
    private function getItem(int $level): Item
    {
        $query = Item::inRandomOrder()->doesntHave('itemSuffix')
            ->doesntHave('itemPrefix')
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('specialty_type')
            ->where('skill_level_required', '<=', $this->rollLevel($level));

        return $query->first();
    }

    /**
     * Roll one prefix and optionally one suffix for the Item level.
     *
     * @param int $level
     * @return array
     */
    private function getAffixes(int $level): array
    {
        $affixes = [];

        $prefixAffix = $this->getRandomAffix('prefix', $level, $this->rollLevel($level));

        if (is_null($prefixAffix)) {
            return [];
        }

        $affixes[] = $prefixAffix;

        if ($this->chanceCalculator->passesPercentage(50.0)) {
            $suffixAffix = $this->getRandomAffix('suffix', $level, $this->rollLevel($level));

            if (! is_null($suffixAffix)) {
                $affixes[] = $suffixAffix;
            }
        }

        return $affixes;
    }

    /**
     * Return an existing exact affix and socket-count Item variant when one exists.
     *
     * @param Item $item
     * @param array $affixes
     * @return Item|null
     */
    private function itemExists(Item $item, array $affixes): ?Item
    {
        $affixIds = [
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ];

        foreach ($affixes as $affix) {
            $affixIds['item_'.$affix->type.'_id'] = $affix->id;
        }

        $query = Item::query()
            ->where('name', $item->name)
            ->where('type', $item->type)
            ->where('base_damage', $item->base_damage)
            ->where('base_ac', $item->base_ac)
            ->where('base_healing', $item->base_healing)
            ->where('item_prefix_id', $affixIds['item_prefix_id'])
            ->where('item_suffix_id', $affixIds['item_suffix_id'])
            ->where('socket_count', $this->socketCount);

        return $query->first();
    }

    /**
     * Create a reusable Item variant with the rolled affixes and socket count.
     *
     * @param Item $item
     * @param array $affixes
     * @return Item
     */
    private function createItem(Item $item, array $affixes): Item
    {
        $item = $item->duplicate();

        $updates = [
            'socket_count' => $this->socketCount,
            'has_gems_socketed' => false,
        ];

        foreach ($affixes as $affix) {
            $updates['item_'.$affix->type.'_id'] = $affix->id;
        }

        $item->update($updates);

        return $item;
    }

    /**
     * Return one random eligible cached affix of the requested type.
     *
     * @param string $type
     * @param int $level
     * @param int $maxRequiredLevel
     * @return ItemAffix|null
     */
    private function getRandomAffix(string $type, int $level, int $maxRequiredLevel): ?ItemAffix
    {
        $this->ensureAffixesCached($type, $level);

        if (! array_key_exists($type, $this->cachedAffixesByType)) {
            return null;
        }

        $affixes = $this->cachedAffixesByType[$type];

        if (count($affixes) === 0) {
            return null;
        }

        $lastEligibleIndex = $this->findLastAffixIndexForMaxRequired($affixes, $maxRequiredLevel);

        if ($lastEligibleIndex < 0) {
            return null;
        }

        $randomIndex = $this->randomNumberGenerator->numberBetween(0, $lastEligibleIndex);

        return $affixes[$randomIndex];
    }

    /**
     * Cache eligible affixes of one type through the requested level.
     *
     * @param string $type
     * @param int $level
     * @return void
     */
    private function ensureAffixesCached(string $type, int $level): void
    {
        $cachedMaxLevel = $this->cachedAffixesMaxLevelByType[$type] ?? 0;

        if ($cachedMaxLevel >= $level) {
            return;
        }

        $this->cachedAffixesByType[$type] = ItemAffix::query()
            ->select(['id', 'type', 'skill_Level_required'])
            ->where('type', $type)
            ->where('skill_Level_required', '<=', $level)
            ->orderBy('skill_Level_required')
            ->orderBy('id')
            ->get()
            ->all();

        $this->cachedAffixesMaxLevelByType[$type] = $level;
    }

    /**
     * Return the last cached affix index allowed by the maximum required level.
     *
     * @param array $affixes
     * @param int $maxRequiredLevel
     * @return int
     */
    private function findLastAffixIndexForMaxRequired(array $affixes, int $maxRequiredLevel): int
    {
        $lowIndex = 0;
        $highIndex = count($affixes) - 1;
        $resultIndex = -1;

        while ($lowIndex <= $highIndex) {
            $midIndex = intdiv($lowIndex + $highIndex, 2);

            $requiredLevel = $affixes[$midIndex]->skill_Level_required ?? 0;

            if ($requiredLevel <= $maxRequiredLevel) {
                $resultIndex = $midIndex;
                $lowIndex = $midIndex + 1;
            } else {
                $highIndex = $midIndex - 1;
            }
        }

        return $resultIndex;
    }

    /**
     * Roll a maximum eligible Item or affix level from one through the supplied level.
     *
     * @param int $level
     * @return int
     */
    protected function rollLevel(int $level): int
    {
        return $this->randomNumberGenerator->numberBetween(1, $level);
    }
}
