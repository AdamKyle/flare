<?php

namespace App\Flare\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use Illuminate\Database\Eloquent\Collection;

class CharacterXPService {

    /**
     * Determine the XP to reward.
     *
     * - Calculate based on two things:
     *   - All quest items that ignore the caps
     *   - All quest items that do not.
     *   - Add both together to get the XP.
     *
     * @param Character $character
     * @param int $xp
     * @return int
     */
    public function determineXPToAward(Character $character, int $xp): int {

        if ($xp === 0) {
            return 0;
        }

        $canContinueLeveling = $this->canContinueLeveling($character);

        $xpBonusQuestSlots   = $this->findAllItemsThatGiveXpBonus($character);

        $xpBonusIgnoreCaps   = $this->getTotalXpBonus($xpBonusQuestSlots, true);
        $xpBonusWithCaps     = $this->getTotalXpBonus($xpBonusQuestSlots, false);

        if ($canContinueLeveling) {

            $xpWithOutCaps = $this->getXP($character, true, $xpBonusIgnoreCaps, $xp);
            $xpWithCaps    = $this->getXP($character, false, $xpBonusWithCaps, $xp);

            return $xpWithOutCaps + $xpWithCaps;
        }

        $xpWithOutCaps = (new MaxLevel($character->level, $xp))->fetchXP(true, $xpBonusIgnoreCaps);
        $xpWithCaps    = (new MaxLevel($character->level, $xp))->fetchXP(false, $xpBonusWithCaps);

        return $xpWithCaps + $xpWithOutCaps;
    }

    /**
     * Can the character gain XP?
     *
     * @param Character $character
     * @return int
     */
    public function canCharacterGainXP(Character $character): int {

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
     * Is the character half way to max?
     *
     * @param int $characterLevel
     * @return bool
     */
    public function isCharacterHalfWay(int $characterLevel): bool {
        $halfWay       = MaxLevelConfiguration::first()->half_way;
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;

        return $characterLevel >= $halfWay && $characterLevel < $threeQuarters;
    }

    /**
     * Are we 75% of the way to max?
     *
     * @param int $characterLevel
     * @return bool
     */
    public function isCharacterThreeQuarters(int $characterLevel): bool {
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;
        $lastLeg       = MaxLevelConfiguration::first()->last_leg;

        return $characterLevel >= $threeQuarters && $characterLevel < $lastLeg;
    }

    /**
     * Are we at the last 100 levels?
     *
     * @param int $characterLevel
     * @return bool
     */
    public function isCharacterAtLastLeg(int $characterLevel): bool {
        $lastLeg  = MaxLevelConfiguration::first()->last_leg;
        $maxLevel = MaxLevelConfiguration::first()->max_level;

        return $characterLevel >= $lastLeg && $characterLevel < $maxLevel;
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
     *
     * @param Character $character
     * @param bool $ignoreCaps
     * @param float $xpBonus
     * @param int $xp
     * @return int|float
     */
    protected function getXP(Character $character, bool $ignoreCaps, float $xpBonus, int $xp): int|float {
        $config = MaxLevelConfiguration::first();

        if (is_null($config)) {
            return (new MaxLevel($character->level, $xp))->fetchXP($ignoreCaps, $xpBonus);
        }

        if ($this->isCharacterHalfWay($character->level) && !$ignoreCaps) {
            return ceil($xp * MaxLevel::HALF_PERCENT);
        }

        if ($this->isCharacterThreeQuarters($character->level) && !$ignoreCaps) {
            return ceil($xp * MaxLevel::THREE_QUARTERS_PERCENT);
        }

        if ($this->isCharacterAtLastLeg($character->level) && !$ignoreCaps) {
            return ceil($xp * MaxLevel::LAST_LEG_PERCENT);
        }

        if ($character->level >= $config->max_level) {
            return 0;
        } else {
            return $xp + $xp * $xpBonus;
        }
    }

    /**
     * Find all quest items that give xp bonus.
     *
     * @param Character $character
     * @return Collection
     */
    protected function findAllItemsThatGiveXpBonus(Character $character): Collection {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('items.id', 'inventory_slots.item_id')->where('items.type', 'quest')->whereNotNull('items.xp_bonus');
        })->select('inventory_slots.*')->get();
    }

    /**
     * Do we have the quest item to keep leveling?
     *
     * @param Character $character
     * @return bool
     */
    protected function canContinueLeveling(Character $character): bool {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('items.id', 'inventory_slots.item_id')->where('items.type', 'quest')->where('items.effect', ItemEffectsValue::CONTINUE_LEVELING);
        })->select('inventory_slots.*')->get()->isNotEmpty();
    }

    /**
     * Get the total xp bonus.
     *
     * @param Collection $questItems
     * @param bool $ignoreCaps
     * @return float
     */
    protected function getTotalXpBonus(Collection $questItems, bool $ignoreCaps): float {
        if ($questItems->isEmpty()) {
            return 0.0;
        }

        if ($ignoreCaps) {
            foreach ($questItems as $slot) {
                dump('Item Name: ' . $slot->item->name . ' gives: ' . $slot->item->xp_bonus);
            }
        }

        return $questItems->where('item.ignores_caps', $ignoreCaps)->sum('item.xp_bonus');
    }
}
