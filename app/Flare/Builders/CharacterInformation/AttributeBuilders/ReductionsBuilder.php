<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;


class ReductionsBuilder extends BaseAttribute {

    public function getRingReduction(string $type): float {
        if (empty($this->inventory)) {
            return 0;
        }

        return $this->inventory->where('item.type', 'ring')->max('item.' . $type);
    }

    public function getAffixReduction($type): float {
        if (empty($this->inventory)) {
             return 0;
        }

        $values = array_merge(
            $this->inventory->pluck('item.itemSuffix.' . $type)->toArray(),
            $this->inventory->pluck('item.itemPrefix.' . $type)->toArray()
        );

        return max($values);
    }
}
