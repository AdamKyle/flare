<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use Illuminate\Support\Collection;

class CharacterAttackInformation {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Collection $inventory
     */
    private $inventory;

    /**
     * @param Character $character
     * @return CharacterAttackInformation
     */
    public function setCharacter(Character $character): CharacterAttackInformation {
        $this->character = $character;

        $this->inventory = $character->inventory->slots->where('equipped', true);

        return $this;
    }

    /**
     * Fetch the inventory for the character with equipped items.
     *
     * @return Collection
     */
    public function fetchInventory(): Collection
    {
        if ($this->inventory->isNotEmpty()) {
            return $this->inventory;
        }

        $inventorySet = $this->character->inventorySets()->where('is_equipped', true)->first();

        if (!is_null($inventorySet)) {
            return $inventorySet->slots;
        }

        return $this->inventory;
    }

    /**
     * Calculates the attribute value based on equipped affixes.
     *
     * @param string $attribute
     * @return float
     */
    public function calulateAttributeValue(string $attribute): float {
        $slots = $this->fetchInventory()->filter(function($slot) use($attribute) {
            if (!is_null($slot->item->itemPrefix))  {
                if ($slot->item->itemPrefix->{$attribute} > 0) {
                    return $slot;
                }
            }

            if (!is_null($slot->item->itemSuffix))  {
                if ($slot->item->itemSuffix->{$attribute} > 0) {
                    return $slot;
                }
            }
        });

        $values = [];

        foreach ($slots as $slot) {
            if (!is_null($slot->item->itemPrefix))  {
                $values[] = $slot->item->itemPrefix->{$attribute};
            }

            if (!is_null($slot->item->itemSuffix))  {
                $values[] = $slot->item->itemSuffix->{$attribute};
            }
        }

        return empty($values) ? 0.0 : max($values);
    }

    /**
     * Find the prefix that reduces stats.
     *
     * We take the first one. It makes it easier than trying to figure out
     * which one is better.
     *
     * These cannot stack.
     *
     * @return ItemAffix|null
     */
    public function findPrefixStatReductionAffix(): ?ItemAffix {
        $slot = $this->fetchInventory()->filter(function($slot) {
            if (!is_null($slot->item->itemPrefix))  {
                if ($slot->item->itemPrefix->reduces_enemy_stats) {
                    return $slot;
                }
            }
        })->first();

        if (!is_null($slot)) {
            return $slot->item->itemPrefix;
        }

        return null;
    }

    /**
     * Finds the life stealing amount for a character.
     *
     * @param bool $canStack
     * @return float
     */
    public function findLifeStealingAffixes(bool $canStack = false): float {
        $slots = $this->fetchInventory()->filter(function($slot) {
            if (!is_null($slot->item->itemPrefix))  {
                if (!is_null($slot->item->itemPrefix->steal_life_amount)) {
                    return $slot;
                }
            }

            if (!is_null($slot->item->itemSuffix))  {
                if (!is_null($slot->item->itemSuffix->steal_life_amount)) {
                    return $slot;
                }
            }
        });

        if ($canStack) {
            $total = ($this->handleLifeStealingAmount($slots, 'itemSuffix') + $this->handleLifeStealingAmount($slots, 'itemPrefix'));

            if ($total > 1.0) {
                return 0.99;
            }

            return $total;
        }

        $values = array_merge(
            $this->fetchAmountOfLifeStealing($slots, 'itemSuffix'),
            $this->fetchAmountOfLifeStealing($slots, 'itemPrefix'),
        );

        if (empty($values)) {
            return 0.0;
        }

        $value = max($values);

        return $value > 1.0 ? .99 : $value;
    }

    /**
     * Gets the total life stealing % from the affixes.
     *
     * @param Collection $slots
     * @param string $type
     * @return float
     */
    protected function handleLifeStealingAmount(Collection $slots, string $type): float {
        $values       = $this->fetchAmountOfLifeStealing($slots, $type);
        $totalPercent = $this->calculateLifeStealingPercentage($values);

        if ($totalPercent > 1.0) {
            return 0.99;
        }

        if (is_null($totalPercent)) {
            return 0.0;
        }

        return $totalPercent;
    }

    protected function fetchAmountOfLifeStealing(Collection $slots, string $affixType): array {
        $values = [];

        foreach ($slots as $slot) {
            if (!is_null($slot->item->{$affixType})) {
                if (empty($values)) {
                    $values[] = $slot->item->{$affixType}->steal_life_amount;
                } else {
                    $values[] = ($slot->item->{$affixType}->steal_life_amount);
                }
            }
        }

        return $values;
    }

    /**
     * Calculates the total life stealing percentage.
     *
     * @param array $values
     * @return float
     */
    protected function calculateLifeStealingPercentage(array $values): float {
        rsort($values);

        $totalPercent     = 0;
        $additionalValues = [];

        foreach ($values as $value) {
            if ($totalPercent === 0) {
                $totalPercent = $value;
            } else {
                $additionalValues[] = ($value / 2);
            }
        }

        $sumOfValues = array_sum($additionalValues);

        if ($sumOfValues > 0) {
            $totalPercent = $totalPercent * ($sumOfValues * 0.75);
        }

        return $totalPercent;
    }
}