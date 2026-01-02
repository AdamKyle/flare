<?php

namespace App\Flare\Items\Builders;

use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;

class RandomItemDropBuilder
{
    private array $cachedAffixesByType = [];

    private array $cachedAffixesMaxLevelByType = [];

    /**
     * Generates the random item for a player.
     */
    public function generateItem(int $forLevel): ?Item
    {
        $item = $this->getItem($forLevel);
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
     * Fetches a random item with not attached affixes.
     *
     * The item cannot be a quest item, artifact item or alchemy item.
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
     * Fetches one or two Affixes.
     */
    private function getAffixes(int $level): array
    {
        $affixes = [];

        $prefixAffix = $this->getRandomAffix('prefix', $level, $this->rollLevel($level));

        if (is_null($prefixAffix)) {
            return [];
        }

        $affixes[] = $prefixAffix;

        if ($this->rollPercent() > 50) {
            $suffixAffix = $this->getRandomAffix('suffix', $level, $this->rollLevel($level));

            if (! is_null($suffixAffix)) {
                $affixes[] = $suffixAffix;
            }
        }

        return $affixes;
    }

    /**
     * Returns a possible item that may already exist with the affixes.
     */
    private function itemExists(Item $item, array $affixes): ?Item
    {
        $query = Item::where('id', $item->id);

        foreach ($affixes as $affix) {
            $column = 'item_'.$affix->type.'_id';
            $query->where($column, $affix->id);
        }

        return $query->first();
    }

    /**
     * Creates a new entry in the database for a new item.
     */
    private function createItem(Item $item, array $affixes): Item
    {
        $item = $item->duplicate();

        $updates = [];

        foreach ($affixes as $affix) {
            $updates['item_'.$affix->type.'_id'] = $affix->id;
        }

        $item->update($updates);

        return $item;
    }

    /**
     * Fetch a random affix without using ORDER BY RAND().
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

        $randomIndex = rand(0, $lastEligibleIndex);

        return $affixes[$randomIndex];
    }

    /**
     * Cache affixes by type and max level for the current process.
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
     * Find last index where skill_Level_required is <= the provided max.
     */
    private function findLastAffixIndexForMaxRequired(array $affixes, int $maxRequiredLevel): int
    {
        $lowIndex = 0;
        $highIndex = count($affixes) - 1;
        $resultIndex = -1;

        while ($lowIndex <= $highIndex) {
            $midIndex = (int) floor(($lowIndex + $highIndex) / 2);

            $requiredLevel = (int) ($affixes[$midIndex]->skill_Level_required ?? 0);

            if ($requiredLevel <= $maxRequiredLevel) {
                $resultIndex = $midIndex;
                $lowIndex = $midIndex + 1;
            } else {
                $highIndex = $midIndex - 1;
            }
        }

        return $resultIndex;
    }

    protected function rollLevel(int $level): int
    {
        return rand(1, $level);
    }

    protected function rollPercent(): int
    {
        return rand(1, 100);
    }
}
