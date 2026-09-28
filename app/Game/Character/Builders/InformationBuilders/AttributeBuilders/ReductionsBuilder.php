<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

class ReductionsBuilder extends BaseAttribute
{
    private float $classMasteryEffect = 0.0;

    /**
     * Set the resolved Gem multiplier applied only to Class Mastery reduction contributions.
     *
     * @param float $classMasteryEffect
     * @return void
     */
    public function setClassMasteryEffect(float $classMasteryEffect): void
    {
        $this->classMasteryEffect = $classMasteryEffect;
    }

    /**
     * Return the strongest equipped ring reduction plus effective Class Mastery reductions.
     *
     * @param string $type
     * @return float
     */
    public function getRingReduction(string $type): float
    {
        if (is_null($this->inventory) || $this->inventory->isEmpty()) {
            return 0;
        }

        $maxValue = $this->inventory->where('item.type', 'ring')->max('item.'.$type);

        $value = ! is_null($maxValue) ? $maxValue : 0;

        $value += $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->sum($type) * (1 + $this->classMasteryEffect);

        return $value;
    }

    /**
     * Return the strongest equipped affix reduction plus effective Class Mastery reductions.
     *
     * @param string $type
     * @return float
     */
    public function getAffixReduction(string $type): float
    {
        if (is_null($this->inventory) || $this->inventory->isEmpty()) {
            return 0;
        }

        $values = array_merge(
            $this->inventory->pluck('item.itemSuffix.'.$type)->toArray(),
            $this->inventory->pluck('item.itemPrefix.'.$type)->toArray()
        );

        $value = max($values);

        $value = ! is_null($value) ? $value : 0;

        $value += $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->sum($type) * (1 + $this->classMasteryEffect);

        return $value;
    }
}
