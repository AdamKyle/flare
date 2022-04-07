<?php

namespace App\Game\Core\Comparison;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Collection;

class ItemComparison {

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

                    $result['position'] = $slot->position;

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

    public function getStatAdjustment(Item $toCompare, Item $equipped, string $stat): int {
        $totalPercentageForEquipped = $equipped->getTotalPercentageForStat($stat);
        $totalPercentageForCompare  = $toCompare->getTotalPercentageForStat($stat);

        return $totalPercentageForCompare - $totalPercentageForEquipped;
    }

    public function getResChanceAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->resurrection_chance - $equipped->resurrection_chance;
    }

    public function getBaseDamageAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->base_damage_mod - $equipped->base_damage_mod;
    }

    public function getBaseHealingAdjustment(Item $toCompare, Item $equipped): int  {
        return $toCompare->base_healing_mod - $equipped->base_healing_mod;
    }

    public function getBaseAcAdjustment(Item $toCompare, Item $equipped): int  {
        return $toCompare->base_ac_mod - $equipped->base_ac_mod;
    }

    public function getFightTimeOutModAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->getTotalFightTimeOutMod() - $equipped->getTotalFightTimeOutMod();
    }

    public function getBaseDamageModAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->getTotalBaseDamageMod() - $equipped->getTotalBaseDamageMod();
    }

    public function getSpellEvasionAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->spell_evasion - $equipped->spell_evasion;
    }

    public function getArtifactAnnulmentAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->artifact_annulment - $equipped->artifact_annulment;
    }

    public function getAmbushChanceAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->ambush_chance - $equipped->ambush_chance;
    }

    public function getAmbushResistanceAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->ambush_resistance - $equipped->ambush_resistance;
    }

    public function getCounterChanceAdjustment(Item $toCompare, Item $equipped): int {
        return $toCompare->counter_chance - $equipped->counter_chance;
    }

    public function getCounterResistanceAdjustment(Item $toCompare, Item $equipped): int {
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
            'ac_adjustment',
            'healing_adjustment',
            'spell_evasion_adjustment',
            'artifact_annulment_adjustment',
            'res_chance_adjustment',
            'base_damage_adjustment',
            'base_healing_adjustment',
            'base_ac_adjustment',
            'fight_time_out_mod_adjustment',
            'base_damage_mod_adjustment',
            'str_adjustment',
            'dur_adjustment',
            'dex_adjustment',
            'chr_adjustment',
            'int_adjustment',
            'agi_adjustment',
            'focus_adjustment',
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

        if (!empty($result)) {
            $result['name'] = $foundPosition->item->affix_name;
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
