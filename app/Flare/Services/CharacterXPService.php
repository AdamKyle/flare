<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use Illuminate\Database\Eloquent\Collection;

class CharacterXPService
{
    /**
     * Determine the XP to reward.
     *
     * - Calculate based on two things:
     *   - All quest items that ignore the caps
     *   - All quest items that do no ignore caps
     *   - Add both together to get the XP.
     */
    public function determineXPToAward(Character $character, int $xp): int
    {

        if ($xp === 0) {
            return 0;
        }

        $canContinueLeveling = $this->canContinueLeveling($character);

        $xpBonusQuestSlots = $this->findAllItemsThatGiveXpBonus($character);
        $boonBonus = $character->boons->sum('itemUsed.xp_bonus');
        $map = $character->map->gameMap;
        $mapBonus = ! is_null($map->xp_bonus) ? $map->xp_bonus : 0;

        $xpBonusIgnoreCaps = $this->getTotalXpBonus($xpBonusQuestSlots, true) + $boonBonus + $mapBonus;
        $xpBonusWithCaps = $this->getTotalXpBonus($xpBonusQuestSlots, false);

        if ($canContinueLeveling) {
            return $this->continueLevelingXpWithBonuses($character, $xp, $xpBonusIgnoreCaps, $xpBonusWithCaps);
        }

        return $this->regularLevelingXpWithBonuses($character, $xp, $xpBonusIgnoreCaps, $xpBonusWithCaps);
    }

    /**
     * Can the character gain XP?
     */
    public function canCharacterGainXP(Character $character): bool
    {

        $canContinueLeveling = $this->canContinueLeveling($character);

        if ($canContinueLeveling) {
            $config = MaxLevelConfiguration::first();

            if (is_null($config)) {
                return $character->level !== MaxLevel::MAX_LEVEL;
            }

            return $character->level !== $config->max_level;
        }

        return $character->level !== MaxLevel::MAX_LEVEL;
    }

    /**
     * Is the character halfway to max?
     */
    public function isCharacterHalfWay(int $characterLevel): bool
    {
        $halfWay = MaxLevelConfiguration::first()->half_way;
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;

        return $characterLevel >= $halfWay && $characterLevel < $threeQuarters;
    }

    /**
     * Are we 75% of the way to max?
     */
    public function isCharacterThreeQuarters(int $characterLevel): bool
    {
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;
        $lastLeg = MaxLevelConfiguration::first()->last_leg;

        return $characterLevel >= $threeQuarters && $characterLevel < $lastLeg;
    }

    /**
     * Are we at the last 100 levels?
     */
    public function isCharacterAtLastLeg(int $characterLevel): bool
    {
        $lastLeg = MaxLevelConfiguration::first()->last_leg;
        $maxLevel = MaxLevelConfiguration::first()->max_level;

        return $characterLevel >= $lastLeg && $characterLevel < $maxLevel;
    }

    /**
     * Get xp when we can continue leveling.
     */
    protected function continueLevelingXpWithBonuses(Character $character, int $xp, float $xpBonusIgnoreCaps, float $xpBonusWithCaps): int
    {
        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps === 0.0) {
            return $this->getXP($character, true, $xpBonusIgnoreCaps, $xp);
        }

        if ($xpBonusWithCaps > 0 && $xpBonusIgnoreCaps === 0.0) {
            return $this->getXP($character, false, $xpBonusWithCaps, $xp);
        }

        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps > 0) {
            $xp = $this->getXP($character, true, $xpBonusIgnoreCaps, $xp);

            return $this->getXP($character, false, $xpBonusWithCaps, $xp);
        }

        return $this->getXP($character, false, $xpBonusWithCaps, $xp);
    }

    /**
     * Get Xp when regular leveling.
     */
    protected function regularLevelingXpWithBonuses(Character $character, int $xp, float $xpBonusIgnoreCaps, float $xpBonusWithCaps): int
    {
        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps === 0.0) {
            return (new MaxLevel($character->level, $xp))->fetchXP(true, $xpBonusIgnoreCaps);
        }

        if ($xpBonusWithCaps > 0 && $xpBonusIgnoreCaps === 0.0) {
            return (new MaxLevel($character->level, $xp))->fetchXP(false, $xpBonusWithCaps);
        }

        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps > 0) {
            $xp = (new MaxLevel($character->level, $xp))->fetchXP(true, $xpBonusIgnoreCaps);

            return (new MaxLevel($character->level, $xp))->fetchXP(false, $xpBonusWithCaps);
        }

        return (new MaxLevel($character->level, $xp))->fetchXP(false, $xpBonusWithCaps);
    }

    /**
     * Get xp.
     *
     * Takes into consideration:
     *
     * - If we can continue leveling
     * - If we should ignore XP caps.
     * - Any additional bonus.
     *
     * All of which is added to the xp.
     */
    protected function getXP(Character $character, bool $ignoreCaps, float $xpBonus, int $xp): float
    {
        $config = MaxLevelConfiguration::first();

        if (is_null($config)) {
            return (new MaxLevel($character->level, $xp))->fetchXP($ignoreCaps, $xpBonus);
        }

        if ($this->isCharacterHalfWay($character->level) && ! $ignoreCaps) {
            return ceil($xp * MaxLevel::HALF_PERCENT);
        }

        if ($this->isCharacterThreeQuarters($character->level) && ! $ignoreCaps) {
            return ceil($xp * MaxLevel::THREE_QUARTERS_PERCENT);
        }

        if ($this->isCharacterAtLastLeg($character->level) && ! $ignoreCaps) {
            return ceil($xp * MaxLevel::LAST_LEG_PERCENT);
        }

        return $xp + $xp * $xpBonus;
    }

    /**
     * Find all quest items that give xp bonus.
     */
    protected function findAllItemsThatGiveXpBonus(Character $character): Collection
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function ($join) {
            $join->on('items.id', 'inventory_slots.item_id')->where('items.type', 'quest')->whereNotNull('items.xp_bonus');
        })->select('inventory_slots.*')->get();
    }

    /**
     * Do we have the quest item to keep leveling?
     */
    protected function canContinueLeveling(Character $character): bool
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return $inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::CONTINUE_LEVELING;
        })->isNotEmpty();
    }

    /**
     * Get the total xp bonus.
     */
    protected function getTotalXpBonus(Collection $questItems, bool $ignoreCaps): float
    {
        if ($questItems->isEmpty()) {
            return 0.0;
        }

        return $questItems->where('item.ignores_caps', $ignoreCaps)->sum('item.xp_bonus');
    }
}
