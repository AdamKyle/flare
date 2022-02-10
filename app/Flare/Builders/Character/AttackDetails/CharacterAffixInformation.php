<?php

namespace App\Flare\Builders\Character\AttackDetails;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\SetSlot;
use Illuminate\Support\Collection;

class CharacterAffixInformation {

    use FetchEquipped;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @param CharacterLifeStealing $characterLifeStealing
     */
    public function __construct(CharacterLifeStealing $characterLifeStealing) {
        $this->characterLifeStealing = $characterLifeStealing;
    }

    /**
     * @param Character $character
     * @return $this
     */
    public function setCharacter(Character $character): CharacterAffixInformation {
        $this->character = $character;

        return $this;
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
            $total = ($this->characterLifeStealing->handleLifeStealingAmount($slots, 'itemSuffix') + $this->characterLifeStealing->handleLifeStealingAmount($slots, 'itemPrefix'));

            if ($this->character->map->gameMap->mapType()->isHell() || $this->character->map->gameMap->mapType()->isPurgatory()) {
                return $total / 2;
            }

            if ($total > 1.0) {
                return 0.99;
            }

            return $total;
        }

        $values = array_merge(
            $this->characterLifeStealing->fetchAmountOfLifeStealing($slots, 'itemSuffix'),
            $this->characterLifeStealing->fetchAmountOfLifeStealing($slots, 'itemPrefix'),
        );

        if (empty($values)) {
            return 0.0;
        }

        $value = max($values);

        if ($this->character->map->gameMap->mapType()->isHell() || $this->character->map->gameMap->mapType()->isPurgatory()) {
            return $value / 2;
        }

        return $value > 1.0 ? .99 : $value;
    }

    /**
     * Fetch all suffix items  that reduce an enemies stats.
     *
     * @return Collection
     */
    public function findSuffixStatReductionAffixes(): Collection {
        return $this->fetchInventory()->filter(function($slot) {
            if (!is_null($slot->item->itemSuffix))  {
                if ($slot->item->itemSuffix->reduces_enemy_stats) {
                    return $slot;
                }
            }
        })->pluck('item.itemSuffix')->values();
    }

    /**
     * Get total affix damage.
     *
     * @param bool $canStack
     * @return int
     */
    public function getTotalAffixDamage(bool $canStack = true): int {
        $slots = $this->fetchInventory()->filter(function ($slot) use ($canStack) {

            if (!is_null($slot->item->item_prefix_id) && $slot->equipped) {
                return $this->getDamageAffixSlot($slot, 'itemPrefix', $canStack);

            }

            if (!is_null($slot->item->item_suffix_id) && $slot->equipped) {
                return $this->getDamageAffixSlot($slot, 'itemSuffix', $canStack);
            }
        });

        return $this->calculateTotalStackingAffixDamage($slots, $canStack);
    }

    /**
     * Do we have any affixes of the applied attribute type?
     *
     * @param string $type
     * @return bool
     */
    public function hasAffixesWithType(string $type): bool {
        return $this->fetchInventory()->filter(function ($slot) use($type) {
            if (!is_null($slot->item->itemPrefix) && !is_null($slot->item->itemSuffix) && $slot->equipped) {
                if ($slot->item->itemPrefix->{$type}) {
                    return $slot;
                }

                if ($slot->item->itemSuffix->{$type}) {
                    return $slot;
                }
            }

            if (!is_null($slot->item->itemPrefix) && $slot->equipped) {
                return $slot->item->itemPrefix->{$type};
            }

            if (!is_null($slot->item->itemSuffix) && $slot->equipped) {
                return $slot->item->itemSuffix->{$type};
            }
        })->isNotEmpty();
    }

    /**
     * Fetch Voidance amount.
     *
     * @param string $type
     * @return float
     */
    public function fetchVoidanceAmount(string $type): float {
        $voidance = 0.0;

        $slots = $this->character->inventory->slots->filter(function($slot) use($type) {
            return $slot->item->type === 'quest' && $slot->item->{$type} > 0;
        });

        if ($slots->isNotEmpty()) {
            foreach ($slots as $slot) {
                $voidance += $slot->item->{$type};
            }
        }

        $amount = $voidance + $this->fetchVoidanceFromAffixes($type);

        if ($this->character->map->gameMap->mapType()->isPurgatory()) {
            return ((($amount * 100) * .45) / 100);
        }

        return $amount;
    }

    /**
     * Fetch voidance from players Affixes
     *
     * @param string $type
     * @return float
     */
    protected function fetchVoidanceFromAffixes(string $type): float {
        $prefixDevouringLight  = $this->fetchInventory()->pluck('item.itemPrefix.' . $type)->toArray();
        $suffixDevouringLight  = $this->fetchInventory()->pluck('item.itemSuffix.' . $type)->toArray();

        $amounts = [...$prefixDevouringLight, ...$suffixDevouringLight];

        if (empty($amounts)) {
            return 0.0;
        }

        $max = max($amounts);

        return is_null($max) ? 0.0 : $max;
    }

    /**
     * @param InventorySlot|SetSlot $slot
     * @param string $prefixType
     * @param bool $canStack
     * @return InventorySlot|SetSlot
     */
    protected function getDamageAffixSlot(InventorySlot|SetSlot $slot, string $prefixType, bool $canStack = false): InventorySlot|SetSlot|null {
        if ($canStack) {
            if ($slot->item->{$prefixType}->damage > 0 && $slot->item->{$prefixType}->damage_can_stack) {
                return $slot;
            }
        } else if ($slot->item->{$prefixType}->damage > 0 && !$slot->item->{$prefixType}->damage_can_stack) {
            return $slot;
        }

        return null;
    }

    /**
     * Calculate the total affix damage.
     *
     * @param Collection $slots
     * @param bool $canStack
     * @return int
     */
    protected function calculateTotalStackingAffixDamage(Collection $slots, bool $canStack = false) {
        if ($canStack) {
            $totalResistibleDamage = $this->calculateStackingAffixDamage($slots);
        } else {
            $totalHighestPrefix = $this->getHighestDamageValueFromAffixes($slots, 'itemPrefix');
            $totalHighestSuffix = $this->getHighestDamageValueFromAffixes($slots, 'itemSuffix');

            if ($totalHighestPrefix > $totalHighestSuffix) {
                return $totalHighestPrefix;
            }

            $totalResistibleDamage = $totalHighestSuffix;
        }

        return $totalResistibleDamage;
    }

    /**
     * Calculate stacking affix damage.
     *
     * @param Collection $slots
     * @return int
     */
    protected function calculateStackingAffixDamage(Collection $slots): int {
        $totalResistibleDamage = 0;

        foreach ($slots as $slot) {
            if (!is_null($slot->item->itemPrefix)) {
                $totalResistibleDamage += $slot->item->itemPrefix->damage;
            }

            if (!is_null($slot->item->itemSuffix)) {
                $totalResistibleDamage += $slot->item->itemSuffix->damage;
            }
        }

        return $totalResistibleDamage;
    }

    /**
     * Get the highest damage value from all affixes.
     *
     * @param Collection $slots
     * @param string $suffixType
     * @return int
     */
    protected function getHighestDamageValueFromAffixes(Collection $slots, string $suffixType): int {
        $values = [];

        foreach ($slots as $slot) {
            if (!is_null($slot->item->{$suffixType})) {
                if ($slot->item->{$suffixType}->damage > 0) {
                    $values[] = $slot->item->{$suffixType}->damage;
                }
            }
        }

        if (empty($values)) {
            return 0;
        }

        return max($values);
    }

    /**
     * Fetch the inventory for the character with equipped items.
     *
     * @return Collection
     */
    protected function fetchInventory(): Collection
    {
        $slots = $this->fetchEquipped($this->character);

        if (is_null($slots)) {
            return collect([]);
        }

        return $slots;
    }
}
