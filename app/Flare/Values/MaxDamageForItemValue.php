<?php

namespace App\Flare\Values;

use App\Flare\Models\Item;

class MaxDamageForItemValue {

    public function fetchMaxDamage(Item $item): int {

        $damage = $item->base_damage;

        if (!is_null($item->artifactProperty)) {
            $damage += $item->artifactProperty->base_damage_mod;
        }

        if ($item->itemAffixes->isNotEmpty()) {
            $item->itemAffixes->each(function($affix) use ($damage) {
                $damage += $affix->base_damage_mod;
            });
        }

        if (is_null($damage)) {
            return 1;
        }

        return $damage;
    }
}
