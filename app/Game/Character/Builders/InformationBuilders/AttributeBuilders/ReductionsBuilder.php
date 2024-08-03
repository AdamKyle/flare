<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

class ReductionsBuilder extends BaseAttribute
{
    public function getRingReduction(string $type): float
    {
        if (empty($this->inventory)) {
            return 0;
        }

        $maxValue = $this->inventory->where('item.type', 'ring')->max('item.'.$type);

        $value = ! is_null($maxValue) ? $maxValue : 0;

        $value += $this->character->classSpecialsEquipped->sum($type);

        return $value;
    }

    public function getAffixReduction($type): float
    {
        if (empty($this->inventory)) {
            return 0;
        }

        $values = array_merge(
            $this->inventory->pluck('item.itemSuffix.'.$type)->toArray(),
            $this->inventory->pluck('item.itemPrefix.'.$type)->toArray()
        );

        $value = max($values);

        $value = ! is_null($value) ? $value : 0;

        $value += $this->character->classSpecialsEquipped->sum($type);

        return $value;
    }
}
