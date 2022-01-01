<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\SetSlot;
use App\Flare\Traits\ClassBasedBonuses;
use App\Flare\Values\CharacterClassValue;
use Illuminate\Support\Collection;

class CharacterAttackInformation {

    use ClassBasedBonuses;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var CharacterInformationBuilder $characterInformationBuilder;
     */
    private $characterInformationBuilder;

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
     * Sets the character information builder.
     *
     * @param CharacterInformationBuilder $characterInformationBuilder
     * @return CharacterAttackInformation
     */
    public function setCharacterInformationBuilder(CharacterInformationBuilder $characterInformationBuilder): CharacterAttackInformation {
        $this->characterInformationBuilder = $characterInformationBuilder;

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

            if ($this->character->map->gameMap->mapType()->isHell() || $this->character->map->gameMap->mapType()->isPurgatory()) {
                return $total / 2;
            }

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
     * Build the amount a character can heal for.
     *
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function buildHealFor(bool $voided = false): int {
        $classBonus    = $this->prophetHealingBonus($this->character) + $this->getVampiresHealingBonus($this->character);

        $classType     = new CharacterClassValue($this->character->class->name);

        $healingAmount = $this->fetchHealingAmount($voided);
        $dmgStat       = $this->character->class->damage_stat;

        if ($classType->isRanger()) {
            if ($voided) {
                $healingAmount += $this->character->chr * 0.15;
            } else {
                $healingAmount += $this->characterInformationBuilder->statMod('chr') * 0.15;
            }

        }

        if ($classType->isProphet()) {
            $hasHealingSpells = $this->prophetHasHealingSpells($this->character);

            if ($hasHealingSpells) {
                if ($voided) {
                    $healingAmount += $this->character->{$dmgStat} * 0.30;
                } else {
                    $healingAmount += $this->characterInformationBuilder->statMod($this->character->{$dmgStat}) * 0.30;
                }
            }
        }

        return round($healingAmount + ($healingAmount * ($this->fetchSkillHealingMod() + $classBonus)));
    }

    public function fetchResurrectionChance(): float {
        $resurrectionItems = $this->fetchInventory()->filter(function($slot) {
            return $slot->item->can_resurrect;
        });

        $chance    = 0.0;
        $classType = new CharacterClassValue($this->character->class->name);

        if ($classType->isProphet()) {
            $chance += 0.05;
        }

        if ($resurrectionItems->isEmpty()) {
            return $chance;
        }

        $chance += $resurrectionItems->sum('item.resurrection_chance');

        return $chance;
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
     * Get total affix damage.
     *
     * @param bool $canStack
     * @return int
     */
    public function getTotalAffixDamage(bool $canStack = true): int {
        $slots = $this->fetchInventory()->filter(function ($slot) use ($canStack) {

            if (!is_null($slot->item->itemPrefix) && $slot->equipped) {
                return $this->getDamageAffixSlot($slot, 'itemPrefix', $canStack);

            }

            if (!is_null($slot->item->itemSuffix) && $slot->equipped) {
                return $this->getDamageAffixSlot($slot, 'itemSuffix', $canStack);
            }
        });

        return $this->calculateTotalStackingAffixDamage($slots, $canStack);
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

        return $voidance + $this->fetchVoidanceFromAffixes($type);
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
        }

        if ($slot->item->{$prefixType}->damage > 0 && !$slot->item->{$prefixType}->damage_can_stack) {
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

        if (is_null($totalPercent)) {
            return 0.0;
        }

        return $totalPercent;
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
     * Fetch the healing amount.
     *
     * @param bool $voided
     * @return int
     */
    protected function fetchHealingAmount(bool $voided = false): int {
        $healFor = 0;

        foreach ($this->fetchInventory() as $slot) {
            if (!$voided) {
                $healFor += $slot->item->getTotalHealing();
            } else {
                $healFor += $slot->item->base_healing;
            }
        }

        return $healFor;
    }

    /**
     * Fetch the skill healing amount modifier
     *
     * @return float
     */
    protected function fetchSkillHealingMod(): float {
        $percentageBonus = 0.0;

        $skills = $this->character->skills->filter(function($skill) {
            return is_null($skill->baseSkill->game_class_id);
        })->all();

        foreach ($skills as $skill) {
            $percentageBonus += $skill->base_healing_mod;
        }

        return $percentageBonus;
    }

    private function fetchVoidanceFromAffixes(string $type): float {
        $prefixDevouringLight  = $this->fetchInventory()->pluck('item.itemPrefix.' . $type)->toArray();
        $sufficDevouringLight  = $this->fetchInventory()->pluck('item.itemSuffix.' . $type)->toArray();

        $amounts = [...$prefixDevouringLight, ...$sufficDevouringLight];

        if (empty($amounts)) {
            return 0.0;
        }

        $max = max($amounts);

        return is_null($max) ? 0.0 : $max;
    }
}