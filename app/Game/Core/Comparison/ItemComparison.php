<?php

namespace App\Game\Core\Comparison;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Collection;

class ItemComparison {

    private $character;

    /**
     * Fetch Comparison Details for an item of the same type currently equipped.
     *
     * @param Item $toCompare
     * @param Collection $inventorySlots
     * @return array
     */
    public function fetchDetails(Item $toCompare, Collection $inventorySlots, Character $character): array {
        $this->character = $character;

        $comparison = [];

        foreach($inventorySlots as $slot) {
            if ($slot->position !== null) {
                $comparison[$slot->position] = $this->fetchHandComparison($toCompare, $inventorySlots, $slot->position);
            }
        }

        return $comparison;
    }

    /**
     * Get Total Damage Increase
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @return int
     */
    public function getDamageIncrease(Item $toCompare, Item $equipped): int {
        $totalDamageForEquipped = $equipped->getTotalDamage();
        $totalDamageForCompare  = $toCompare->getTotalDamage();

        return $totalDamageForCompare - $totalDamageForEquipped;
    }

    /**
     * Get Total Damage Decrease
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @return int
     */
    public function getDamageDecrease(Item $toCompare, Item $equipped): int {
        $totalDamageForEquipped = $equipped->getTotalDamage();
        $totalDamageForCompare  = $toCompare->getTotalDamage();

        if ($totalDamageForCompare < $totalDamageForEquipped) {
            return $totalDamageForCompare - $totalDamageForEquipped;
        }

        return 0;
    }

    /**
     * Get Total Ac Increase
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @return int
     */
    public function getAcIncrease(Item $toCompare, Item $equipped): int {
        $totalDefenceForEquipped = $equipped->getTotalDefence();
        $totalDefenceForCompare  = $toCompare->getTotalDefence();

        if ($totalDefenceForEquipped === 0.0) {
            return 0;
        }

        return $totalDefenceForCompare - $totalDefenceForEquipped;
    }

    /**
     * Get Total Ac Decrease
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @return int
     */
    public function getAcDecrease(Item $toCompare, Item $equipped): int {
        $totalDefenceForEquipped = $equipped->getTotalDefence();
        $totalDefenceForCompare  = $toCompare->getTotalDefence();

        return $totalDefenceForCompare - $totalDefenceForEquipped;
    }

    /**
     * Get Total Heal Increase
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @return int
     */
    public function getHealIncrease(Item $toCompare, Item $equipped): int {
        $totalHealForEquipped = $equipped->getTotalHealing();
        $totalHealForCompare  = $toCompare->getTotalHealing();

        return $totalHealForCompare - $totalHealForEquipped;
    }

    /**
     * Get Total Heal Decrease
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @return int
     */
    public function getHealDecrease(Item $toCompare, Item $equipped): int {
        $totalHealForEquipped = $equipped->getTotalHealing();
        $totalHealForCompare  = $toCompare->getTotalHealing();

        return $totalHealForCompare - $totalHealForEquipped;
    }

    /**
     * Get Total Stat Increase
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @param string $stat
     * @return int
     */
    public function getStatIncrease(Item $toCompare, Item $equipped, string $stat): float {
        $totalPercentageForEquipped = $equipped->getTotalPercentageForStat($stat);
        $totalPercentageForCompare  = $toCompare->getTotalPercentageForStat($stat);

        return $totalPercentageForCompare - $totalPercentageForEquipped;
    }


    /**
     * Get Total Stat Decrease
     *
     * @param Item $toCompare
     * @param Item $equipped
     * @param string $stat
     * @return int
     */
    public function getStatDecrease(Item $toCompare, Item $equipped, string $stat): float {
        $totalPercentageForEquipped = $equipped->getTotalPercentageForStat($stat);
        $totalPercentageForCompare  = $toCompare->getTotalPercentageForStat($stat);

        return $totalPercentageForCompare - $totalPercentageForEquipped;
    }

    protected function fetchHandComparison(Item $toCompare, Collection $inventorySlots, string $hand): array {

        $foundPosition = $inventorySlots->filter(function($slot) use ($hand) {
            return $slot->position === $hand;
        })->first();

        if ($this->isItemBetter($toCompare, $foundPosition->item)) {
            return [
                'is_better'                     => true,
                'replaces_item'                 => $foundPosition->item,
                'slot'                          => $foundPosition,
                'position'                      => $foundPosition->position,
                'damage_adjustment'             => $this->getDamageIncrease($toCompare, $foundPosition->item),
                'ac_adjustment'                 => $this->getAcIncrease($toCompare, $foundPosition->item),
                'healing_adjustment'            => $this->getHealIncrease($toCompare, $foundPosition->item),
                'spell_evasion_adjustment'      => $toCompare->spell_evasion - $foundPosition->item->spell_evasion,
                'artifact_annulment_adjustment' => $toCompare->artifact_annulment - $foundPosition->item->artifact_annulment,
                'res_chance_adjustment'         => $toCompare->resurrection_chance - $foundPosition->item->resurrection_chance,
                'base_damage_adjustment'        => $toCompare->base_damage_mod - $foundPosition->item->base_damage_mod,
                'base_healing_adjustment'       => $toCompare->base_healing_mod - $foundPosition->item->base_healing_mod,
                'base_ac_adjustment'            => $toCompare->base_ac_mod - $foundPosition->item->base_ac_mod,
                'fight_timeout_mod_adjustment'  => $toCompare->getTotalFightTimeOutMod() - $foundPosition->item->getTotalFightTimeOutMod(),
                'base_damage_mod_adjustment'    => $toCompare->getTotalBaseDamageMod() - $foundPosition->item->getTotalBaseDamageMod(),
                'base_damage_mod_adjustment'    => $toCompare->getTotalBaseDamageMod() - $foundPosition->item->getTotalBaseDamageMod(),
                'str_adjustment'                => $this->getStatIncrease($toCompare, $foundPosition->item, 'str'),
                'dur_adjustment'                => $this->getStatIncrease($toCompare, $foundPosition->item, 'dur'),
                'dex_adjustment'                => $this->getStatIncrease($toCompare, $foundPosition->item, 'dex'),
                'chr_adjustment'                => $this->getStatIncrease($toCompare, $foundPosition->item, 'chr'),
                'int_adjustment'                => $this->getStatIncrease($toCompare, $foundPosition->item, 'int'),
                'agi_adjustment'                => $this->getStatIncrease($toCompare, $foundPosition->item, 'agi'),
                'focus_adjustment'              => $this->getStatIncrease($toCompare, $foundPosition->item, 'focus'),
            ];
        } else {
            $baseDamageAdjustment  = $toCompare->base_damage_mod < $foundPosition->item->base_damage_mod ? $toCompare->base_damage_mod - $foundPosition->item->base_damage_mod : $toCompare->base_damage_mod;
            $baseHealingAdjustment = $toCompare->base_healing_mod < $foundPosition->item->base_healing_mod ? $toCompare->base_healing_mod - $foundPosition->item->base_healing_mod : $toCompare->base_healing_mod;
            $baseAcAdjustment     = $toCompare->base_ac_mod < $foundPosition->item->base_ac_mod ? $toCompare->base_ac_mod - $foundPosition->item->base_ac_mod : $toCompare->base_ac_mod;

            return [
                'is_better'                     => false,
                'replaces_item'                 => null,
                'slot'                          => $foundPosition,
                'position'                      => $foundPosition->position,
                'damage_adjustment'             => $this->getDamageDecrease($toCompare, $foundPosition->item),
                'ac_adjustment'                 => $this->getAcDecrease($toCompare, $foundPosition->item),
                'healing_adjustment'            => $this->getHealDecrease($toCompare, $foundPosition->item),
                'spell_evasion_adjustment'      => $toCompare->spell_evasion - $foundPosition->item->spell_evasion,
                'artifact_annulment_adjustment' => $toCompare->artifact_annulment - $foundPosition->item->artifact_annulment,
                'res_chance_adjustment'         => $toCompare->resurrection_chance - $foundPosition->item->resurrection_chance,
                'str_adjustment'                => $this->getStatDecrease($toCompare, $foundPosition->item, 'str'),
                'dur_adjustment'                => $this->getStatDecrease($toCompare, $foundPosition->item, 'dur'),
                'dex_adjustment'                => $this->getStatDecrease($toCompare, $foundPosition->item, 'dex'),
                'chr_adjustment'                => $this->getStatDecrease($toCompare, $foundPosition->item, 'chr'),
                'int_adjustment'                => $this->getStatDecrease($toCompare, $foundPosition->item, 'int'),
                'agi_adjustment'                => $this->getStatIncrease($toCompare, $foundPosition->item, 'agi'),
                'focus_adjustment'              => $this->getStatIncrease($toCompare, $foundPosition->item, 'focus'),
                'base_damage_adjustment'        => $baseDamageAdjustment,
                'base_healing_adjustment'       => $baseHealingAdjustment,
                'base_ac_adjustment'            => $baseAcAdjustment,
                'fight_timeout_mod_adjustment'  => $toCompare->getTotalFightTimeOutMod() - $foundPosition->item->getTotalFightTimeOutMod(),
                'base_damage_mod_adjustment'    => $toCompare->getTotalBaseDamageMod() - $foundPosition->item->getTotalBaseDamageMod(),
            ];
        }
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
