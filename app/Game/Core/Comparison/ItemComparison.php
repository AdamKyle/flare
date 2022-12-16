<?php

namespace App\Game\Core\Comparison;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;
use Illuminate\Database\Eloquent\Collection;

class ItemComparison {

    use IsItemUnique;

    private $character;

    private $coreStats = [
        'str',
        'dur',
        'dex',
        'chr',
        'int',
        'agi',
        'focus',
    ];

    /**
     * Fetch Comparison Details for an item of the same type currently equipped.
     *
     * @param Item $toCompare
     * @param Collection $inventorySlots
     * @param Character $character
     * @return array
     */
    public function fetchDetails(Item $toCompare, Collection $inventorySlots, Character $character): array {
        $this->character = $character;

        $comparison = [];

        foreach($inventorySlots as $slot) {
            if ($slot->position !== null) {
                $result = $this->fetchHandComparison($toCompare, $inventorySlots, $slot->position);

                if (!empty($result)) {

                    $result['position']            = $slot->position;
                    $result['is_unique']           = $this->isUnique($slot->item);
                    $result['affix_count']         = $slot->item->affix_count;
                    $result['holy_stacks_applied'] = $slot->item->holy_stacks_applied;

                    $comparison[] = $result;
                }
            }
        }

        return $comparison;
    }

    public function getDamageAdjustment(Item $toCompare, Item $equipped): int {
        $totalDamageForEquipped = $equipped->getTotalDamage();
        $totalDamageForCompare  = $toCompare->getTotalDamage();

        return $totalDamageForCompare - $totalDamageForEquipped;
    }

    public function getAcAdjustment(Item $toCompare, Item $equipped): int {
        $totalDefenceForEquipped = $equipped->getTotalDefence();
        $totalDefenceForCompare  = $toCompare->getTotalDefence();

        return $totalDefenceForCompare - $totalDefenceForEquipped;
    }

    public function getHealingAdjustment(Item $toCompare, Item $equipped): int {
        $totalHealForEquipped = $equipped->getTotalHealing();
        $totalHealForCompare  = $toCompare->getTotalHealing();

        return $totalHealForCompare - $totalHealForEquipped;
    }

    public function getStatAdjustment(Item $toCompare, Item $equipped, string $stat): float {
        $totalPercentageForEquipped = $equipped->getTotalPercentageForStat($stat);
        $totalPercentageForCompare  = $toCompare->getTotalPercentageForStat($stat);

        return $totalPercentageForCompare - $totalPercentageForEquipped;
    }

    public function getResChanceAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->resurrection_chance - $equipped->resurrection_chance;
    }

    public function getBaseDamageAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->base_damage_mod - $equipped->base_damage_mod;
    }

    public function getBaseHealingAdjustment(Item $toCompare, Item $equipped): float  {
        return $toCompare->base_healing_mod - $equipped->base_healing_mod;
    }

    public function getBaseAcAdjustment(Item $toCompare, Item $equipped): float  {
        return $toCompare->base_ac_mod - $equipped->base_ac_mod;
    }

    public function getFightTimeOutModAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->getTotalFightTimeOutMod() - $equipped->getTotalFightTimeOutMod();
    }

    public function getBaseDamageModAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->getTotalBaseDamageMod() - $equipped->getTotalBaseDamageMod();
    }

    public function getSpellEvasionAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->spell_evasion - $equipped->spell_evasion;
    }

    public function getAmbushChanceAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->ambush_chance - $equipped->ambush_chance;
    }

    public function getAmbushResistanceAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->ambush_resistance - $equipped->ambush_resistance;
    }

    public function getCounterChanceAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->counter_chance - $equipped->counter_chance;
    }

    public function getCounterResistanceAdjustment(Item $toCompare, Item $equipped): float {
        return $toCompare->counter_resistance - $equipped->counter_resistance;
    }

    protected function fetchHandComparison(Item $toCompare, Collection $inventorySlots, string $hand): array {

        $foundPosition = $inventorySlots->filter(function($slot) use ($hand) {
            return $slot->position === $hand;
        })->first();

        if (is_null($foundPosition)) {
            return [];
        }

        $adjustments = [
            'damage_adjustment',
            'base_damage_adjustment',
            'base_damage_mod_adjustment',
            'ac_adjustment',
            'base_ac_adjustment',
            'healing_adjustment',
            'base_healing_adjustment',
            'str_adjustment',
            'dur_adjustment',
            'dex_adjustment',
            'chr_adjustment',
            'int_adjustment',
            'agi_adjustment',
            'focus_adjustment',
            'fight_time_out_mod_adjustment',
            'spell_evasion_adjustment',
            'res_chance_adjustment',
            'ambush_chance_adjustment',
            'ambush_resistance_adjustment',
            'counter_chance_adjustment',
            'counter_resistance_adjustment',
        ];

        $result = [];

        foreach ($adjustments as $adjustmentType) {
            $parts = explode('_', $adjustmentType);

            if (in_array($parts[0], $this->coreStats)) {
                $adjustment = $this->getStatAdjustment($toCompare, $foundPosition->item, $parts[0]);

                $result[$adjustmentType] = $adjustment;
            } else {
                $function   = 'get' . ucfirst(camel_case($adjustmentType));

                $adjustment = $this->{$function}($toCompare, $foundPosition->item);

                $result[$adjustmentType] = $adjustment;
            }
        }

        $result = $this->getAffixComparisons($toCompare, $foundPosition->item, $result);

        if (!empty($result)) {
            $result['name']   = $foundPosition->item->affix_name;
            $result['skills'] = $this->addSkillComparison($toCompare, $foundPosition->item, $result);
        }

        return $result;
    }

    /**
     * Compares skills on attached affixes.
     *
     * @param Item $toCompare
     * @param Item $equippedItem
     * @param array $result
     * @return array
     */
    protected function addSkillComparison(Item $toCompare, Item $equippedItem, array $result): array {
        $toCompareSkills = $toCompare->getItemSkills();
        $equippedItemSkills = $equippedItem->getItemSkills();

        if (empty($toCompareSkills) && !empty($equippedItemSkills)) {

            foreach ($equippedItemSkills as $index => $skill) {
                $equippedItemSkills[$index]['skill_training_bonus'] = -$equippedItemSkills[$index]['skill_training_bonus'];
                $equippedItemSkills[$index]['skill_bonus']          = -$equippedItemSkills[$index]['skill_bonus'];
            }

            return $equippedItemSkills;
        }

        if (!empty($toCompareSkills) && empty($equippedItemSkills)) {
            return $toCompareSkills;
        }

        $comparison = [];

        foreach ($toCompareSkills as $index => $skillDetails) {
            if (!isset($equippedItemSkills[$index])) {
                continue;
            }

            if ($skillDetails['skill_name'] === $equippedItemSkills[$index]['skill_name']) {
                $comparison[] = [
                    'skill_name'           => $skillDetails['skill_name'],
                    'skill_training_bonus' => $skillDetails['skill_training_bonus'] - $equippedItemSkills[$index]['skill_training_bonus'],
                    'skill_bonus'          => $skillDetails['skill_bonus'] - $equippedItemSkills[$index]['skill_bonus'],
                ];
            } else {
                $comparison[] = $skillDetails;
            }
        }

        return $comparison;
    }

    /**
     * Get Affix Comparisons.
     *
     * @param Item $toCompare
     * @param Item $equippedItem
     * @param array $result
     * @return array
     */
    protected function getAffixComparisons(Item $toCompare, Item $equippedItem, array $result): array {
        $attributes = [
            'str_reduction',
            'dur_reduction',
            'dex_reduction',
            'chr_reduction',
            'int_reduction',
            'agi_reduction',
            'focus_reduction',
            'reduces_enemy_stats',
            'steal_life_amount',
            'entranced_chance',
            'damage',
            'class_bonus',
        ];

        foreach ($attributes as $attribute) {
            $toEquipAttribute  = $toCompare->getAffixAttribute($attribute);
            $equippedAttribute = $equippedItem->getAffixAttribute($attribute);

            $result[$attribute] = ($toEquipAttribute - $equippedAttribute);
        }

        return $result;
    }


    protected function isItemTwoHanded(Item $item): bool {
        return in_array($item->type, ['bow', 'hammer', 'stave']);
    }

    protected function isItemBetter(Item $toCompare, Item $equipped): bool {
        $totalDamageForEquipped = $equipped->getTotalDamage();
        $totalDamageForCompare  = $toCompare->getTotalDamage();

        $totalDefenceForEquipped = $equipped->getTotalDefence();
        $totalDefenceCompare     = $toCompare->getTotalDefence();

        $totalHealingEquipped = $equipped->getTotalHealing();
        $totalHealingCompare  = $toCompare->getTotalHealing();

        $totalStatForEquipped = $equipped->getTotalPercentageForStat($this->character->damage_stat);
        $totalStatForCompare  = $toCompare->getTotalPercentageForStat($this->character->damage_stat);

        if ($totalStatForEquipped > 0.0) {
            if ($totalStatForCompare > $totalStatForEquipped) {
                return true;
            }
        }

        if ($totalDamageForCompare > $totalDamageForEquipped) {
            return true;
        }

        if ($totalDefenceCompare > $totalDefenceForEquipped) {
            return true;
        }

        if ($totalHealingCompare > $totalHealingEquipped) {
            return true;
        }

        return false;
    }
}
