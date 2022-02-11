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

    public function determineXPToAward(Character $character, int $xp): int {

        if ($xp === 0) {
            return 0;
        }

        $canContinueLeveling = $this->canContinueLeveling($character);
        $XpBonusQuestSlots   = $this->findAllItemsThatGiveXpBonus($character);
        $ignoreCaps          = $this->shouldIgnoreCaps($XpBonusQuestSlots);
        $xpBonus             = $this->getTotalXPBonus($XpBonusQuestSlots);

        if ($canContinueLeveling) {
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

            if ($character->level === $config->max_level) {
                return 0;
            } else {
                return $xp + $xp * $xpBonus;
            }
        }

        return (new MaxLevel($character->level, $xp))->fetchXP($ignoreCaps, $xpBonus);
    }

    public function isCharacterHalfWay(int $characterLevel): bool {
        $halfWay       = MaxLevelConfiguration::first()->half_way;
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;

        return $characterLevel >= $halfWay && $characterLevel < $threeQuarters;
    }

    public function isCharacterThreeQuarters(int $characterLevel): bool {
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;
        $lastLeg       = MaxLevelConfiguration::first()->last_leg;

        return $characterLevel >= $threeQuarters && $characterLevel < $lastLeg;
    }

    public function isCharacterAtLastLeg(int $characterLevel): bool {
        $lastLeg  = MaxLevelConfiguration::first()->last_leg;
        $maxLevel = MaxLevelConfiguration::first()->max_level;

        return $characterLevel >= $lastLeg && $characterLevel < $maxLevel;
    }

    protected function findAllItemsThatGiveXpBonus(Character $character): Collection {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('items.id', 'inventory_slots.item_id')->where('items.type', 'quest');
        })->select('inventory_slots.*')->get();
    }

    protected function canContinueLeveling(Character $character): bool {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function($join) {
            $join->on('items.id', 'inventory_slots.item_id')->where('items.type', 'quest')->where('items.effect', ItemEffectsValue::CONTNUE_LEVELING);
        })->select('inventory_slots.*')->get()->isNotEmpty();
    }

    protected function shouldIgnoreCaps(Collection $questItems): bool {
        return $questItems->filter(function($slot)  {
            return $slot->item->ignores_caps;
        })->isNotEmpty();
    }

    protected function getTotalXpBonus(Collection $questItems): float {
        if ($questItems->isEmpty()) {
            return 0.0;
        }

        return $questItems->sum('item.xp_bonus');
    }
}
