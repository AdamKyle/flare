<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Traits\ClassBasedBonuses;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemUsabilityType;
use Illuminate\Support\Collection;

class CharacterInformationBuilder {

    use ClassBasedBonuses;

    /**
     * @var CharacterAttackInformation $characterAttackInformation
     */
    private $characterAttackInformation;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Illuminate\Support\Collection $inventory
     */
    private $inventory;

    /**
     * @param CharacterAttackInformation $characterAttackInformation
     */
    public function __construct(CharacterAttackInformation $characterAttackInformation) {
        $this->characterAttackInformation = $characterAttackInformation;
    }

    /**
     * Set the character and fetch its inventory.
     *
     * @param Character $character
     * @return CharacterInformationBuilder
     */
    public function setCharacter(Character $character): CharacterInformationBuilder {
        $this->character = $character;

        $this->inventory = $character->inventory->slots->where('equipped', true);

        return $this;
    }

    public function getCharacter(): Character {
        return $this->character;
    }

    /**
     * Get the characters total stat mode for a stat
     *
     * Applies all bonuses to that stat based on equipped items in the
     * inventory assuming the user has anything equipped at all.
     *
     * @param string $stat
     * @return mixed
     */
    public function statMod(string $stat) {
        $base = $this->character->{$stat};

        $equipped = $this->fetchInventory()->filter(function($slot) {
            return $slot->equipped;
        });

        if ($equipped->isEmpty()) {
            return $this->characterBoons($base);
        }

        foreach ($equipped as $slot) {
            $base += $base * $this->fetchModdedStat($stat, $slot->item);
        }

        return $this->characterBoons($base);
    }

    /**
     * Gets a specific skill based on name.
     *
     * @param string $skillName
     * @return float
     */
    public function getSkill(string $skillName): float {
        $skill = $this->character->skills->filter(function($skill) use ($skillName) {
            return $skill->name === $skillName;
        })->first();

        return $skill->skill_bonus;
    }

    /**
     * Return the highest class bonus affix amount.
     *
     * Class bonuses do not stack, therefore we only return the highest valued
     * version.
     *
     * @return float
     */
    public function classBonus(): float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->calulateAttributeValue('class_bonus');
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
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->findPrefixStatReductionAffix();
    }

    /**
     * Finds the life stealing amount for a character.
     *
     * @param bool $canStack
     * @return float
     */
    public function findLifeStealingAffixes(bool $canStack = false): float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->findLifeStealingAffixes($canStack);
    }

    public function getEntrancedChance(): float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->calulateAttributeValue('entranced_chance');
    }

    public function getBestSkillReduction() : float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->calulateAttributeValue('skill_reduction');
    }

    public function getBestResistanceReduction() : float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->calulateAttributeValue('resistance_reduction');
    }

    /**
     * Returns a collection of single stat reduction affixes.
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

    public function buildCharacterDamageStat(bool $voided = false): float|int {
        $characterDamageStat = $this->statMod($this->character->damage_stat);

        if ($voided) {
            $characterDamageStat = $this->character->{$this->character->damage_stat};
        }

        $classType = $this->character->classType();

        if ($classType->isFighter() || $classType->isRanger() || $classType->isThief()) {
            return $characterDamageStat * 0.15;
        }

        return $characterDamageStat * 0.05;
    }

    /**
     * Builds Total Attack.
     *
     * @return int
     * @throws \Exception
     */
    public function buildTotalAttack(): int {
        return $this->getWeaponDamage() + $this->getSpellDamage() + $this->getTotalArtifactDamage() + $this->getTotalRingDamage();
    }

    /**
     * Build the defence
     *
     * Fetches the defence based off a base of ten plus the equipment, skills and other
     * bonuses.
     *
     * @return int
     */
    public function buildDefence(bool $voided = false): int {
        return round((10 + $this->getDefence($voided)) * (1 + $this->fetchSkillACMod() + $this->getFightersDefence($this->character)));
    }

    /**
     * Build heal for
     *
     * Fetches the total healing amount based on skills and equipment.
     *
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
                $healingAmount += $this->statMod('chr') * 0.15;
            }

        }

        if ($classType->isProphet()) {
            $hasHealingSpells = $this->prophetHasHealingSpells($this->character);

            if ($hasHealingSpells) {
                if ($voided) {
                    $healingAmount += $this->character->{$dmgStat} * 0.30;
                } else {
                    $healingAmount += $this->statMod($this->character->{$dmgStat}) * 0.30;
                }
            }
        }

        return round($healingAmount + ($healingAmount * ($this->fetchSkillHealingMod() + $classBonus)));
    }

    /**
     * Fetch the resurrection chance;
     *
     * @return float
     * @throws \Exception
     */
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
     * Build total health
     *
     * Build the characters health based off equipment, plus the characters health and
     * a base of 10.
     *
     * @return int
     */
    public function buildHealth(bool $voided = false): int {

        if ($this->character->is_dead) {
            return 0;
        }

        $baseHealth = $this->character->dur + 10;

        if ($voided) {
            return $baseHealth;
        }

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->equipped) {
                $percentage = $slot->item->getTotalPercentageForStat('dur');

                $baseHealth += $baseHealth * $percentage;
            }
        }

        return $baseHealth;
    }

    /**
     * Does the character have any artifacts?
     *
     * @return bool
     */
    public function hasArtifacts(): bool {
        return $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'artifact' && $slot->equipped;
        })->isNotEmpty();
    }

    /**
     * Does the character have any items with affixes?
     *
     * @return bool
     */
    public function hasAffixes(): bool {
        return $this->fetchInventory()->filter(function ($slot) {
            return !is_null($slot->item->itemPrefix || (!is_null($slot->item->itemSuffix)) && $slot->equipped);
        })->isNotEmpty();
    }

    /**
     * Can your affixes be resisted at all?
     *
     * If you have the quest item that has the AFFIXES_IRRESISTIBLE
     * effect, then you cannot be resisted for affixes.
     *
     * @return bool
     */
    public function canAffixesBeResisted(): bool {
        return $this->character->inventory->slots->filter(function($slot) {
            return ($slot->item->type === 'quest') && ($slot->item->effect === ItemEffectsValue::AFFIXES_IRRESISTIBLE);
        })->isNotEmpty();
    }

    public function hasIrresistibleAffix(): bool {
        return $this->fetchInventory()->filter(function ($slot) {
            if (!is_null($slot->item->itemPrefix) && !is_null($slot->item->itemSuffix) && $slot->equipped) {
                if ($slot->item->itemPrefix->irresistible_damage) {
                    return $slot;
                }

                if ($slot->item->itemSuffix->irresistible_damage) {
                    return $slot;
                }
            }

            if (!is_null($slot->item->itemPrefix) && $slot->equipped) {
                return $slot->item->itemPrefix->irresistible_damage;
            }

            if (!is_null($slot->item->itemSuffix) && $slot->equipped) {
                return $slot->item->itemSuffix->irresistible_damage;
            }
        })->isNotEmpty();
    }

    /**
     * Determine the affix damage.
     *
     * Some affixes cannot stack their damage, so we only return the highest if you pass in false.
     *
     * If you want the stacking ones only, then this will return the total value of those.
     *
     * Fetches from both prefix and suffix.
     *
     * @param bool $canStack
     * @return int
     */
    public function getTotalAffixDamage(bool $canStack = true): int {
        $slots = $this->fetchInventory()->filter(function ($slot) use ($canStack) {

            if (!is_null($slot->item->itemPrefix) && $slot->equipped) {

                if ($canStack) {
                    if ($slot->item->itemPrefix->damage > 0 && $slot->item->itemPrefix->damage_can_stack) {
                        return $slot;
                    }
                } else {
                    if ($slot->item->itemPrefix->damage > 0 && !$slot->item->itemPrefix->damage_can_stack) {
                        return $slot;
                    }
                }

            }

            if (!is_null($slot->item->itemSuffix) && $slot->equipped) {
                if ($canStack) {
                    if ($slot->item->itemSuffix->damage > 0 && $slot->item->itemSuffix->damage_can_stack) {
                        return $slot;
                    }
                } else {
                    if ($slot->item->itemSuffix->damage > 0 && !$slot->item->itemSuffix->damage_can_stack) {
                        return $slot;
                    }
                }
            }
        });

        $totalResistibleDamage = 0;

        if ($canStack) {
            foreach ($slots as $slot) {
                if (!is_null($slot->item->itemPrefix)) {
                    $totalResistibleDamage += $slot->item->itemPrefix->damage;
                }

                if (!is_null($slot->item->itemSuffix)) {
                    $totalResistibleDamage += $slot->item->itemSuffix->damage;
                }
            }
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
     * Does the character have any damage spells
     *
     * @return bool
     */
    public function hasDamageSpells(): bool {
        return $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'spell-damage' && $slot->equipped;
        })->isNotEmpty();
    }

    public function getTotalWeaponDamage(bool $voided = false): int {
        return $this->getWeaponDamage($voided);
    }

    /**
     * Get the total Spell Damage
     *
     * @return int
     */
    public function getTotalSpellDamage(bool $voided = false): int {
        return $this->getSpellDamage($voided);
    }

    /**
     * Get the total artifact damage.
     *
     * @return int
     */
    public function getTotalArtifactDamage(): int {
        return $this->getArtifactDamage();
    }

    /**
     * Gets the total ring damage.
     *
     * @return int
     */
    public function getTotalRingDamage(bool $voided = false): int {
        return $this->getRingDamage($voided);
    }

    public function getTotalDeduction(string $type): float {
        return $this->getDeduction($type);
    }

    public function getDevouringLight(): float {
        return $this->fetchVoidanceAmount('devouring_light');
    }

    public function getDevouringDarkness(): float {
        return $this->fetchVoidanceAmount('devouring_darkness');
    }

    /**
     * Get total annulment
     *
     * @return float
     */
    public function getTotalAnnulment(): float {
        return $this->getArtifactAnnulment();
    }

    /**
     * Get total spell evasion
     *
     * @return float
     */
    public function getTotalSpellEvasion(): float {
        return  $this->getSpellEvasion();
    }

    protected function fetchVoidanceAmount(string $type): float {
        $voidance = 0.0;

        $slot = $this->character->inventory->slots->filter(function($slot) use($type) {
           return $slot->item->type === 'quest' && $slot->item->{$type} > 0;
        })->first();

        if (!is_null($slot)) {
            $voidance = $slot->item->{$type};
        }

        return $voidance + $this->fetchVoidanceFromAffixes($type);
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

    /**
     * applies character boons to the
     * @param $base
     * @return float|int
     */
    protected function characterBoons($base) {
        if ($this->character->boons->isNotEmpty()) {
            $boons = $this->character->boons()->where('type', ItemUsabilityType::STAT_INCREASE)->get();

            if ($boons->isNotEmpty()) {
                $sum = $boons->sum('stat_bonus');

                $base += $base + $base * $sum;
            }
        }

        return $base;
    }

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

    protected function getDeduction(string $type): float {
        $itemsDeduction = $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'ring' && $slot->equipped;
        })->pluck('item.' . $type)->toArray();

        if (empty($itemsDeduction)) {
            return 0.0;
        }

        return max($itemsDeduction);
    }

    protected function getSpellEvasion(): float {
        $itemsEvasion = $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'ring' && $slot->equipped;
        })->pluck('item.spell_evasion')->toArray();

        if (empty($itemsEvasion)) {
            return 0.0;
        }

        return max($itemsEvasion);
    }

    protected function getArtifactAnnulment(): float {
        $itemsEvasion = $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'ring' && $slot->equipped;
        })->pluck('item.artifact_annulment')->toArray();

        if (empty($itemsEvasion)) {
            return 0.0;
        }

        return max($itemsEvasion);
    }

    protected function getHealingReduction(): float {
        $itemsEvasion = $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'ring' && $slot->equipped;
        })->pluck('item.healing_reduction')->toArray();

        return max($itemsEvasion);
    }

    protected function fetchSkillAttackMod(): float {
        $percentageBonus = 0.0;

        $skills = $this->character->skills->filter(function($skill) {
            return !is_null($skill->baseSkill->game_class_id);
        })->all();

        foreach ($skills as $skill) {
            $percentageBonus += $skill->base_damage_mod;
        }

        return $percentageBonus;
    }

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

    protected function fetchSkillACMod(): float {
        $percentageBonus = 0.0;

        $skills = $this->character->skills->filter(function($skill) {
            return is_null($skill->baseSkill->game_class_id);
        })->all();

        foreach ($skills as $skill) {
            $percentageBonus += $skill->base_ac_mod;
        }

        return $percentageBonus;
    }

    protected function getWeaponDamage(bool $voided = false): int {
        $damage = [];

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'weapon') {
                if (!$voided) {
                    $damage[] = $slot->item->getTotalDamage();
                } else {
                    $damage[] =  $slot->item->base_damage;
                }
            } else if ($slot->item->type === 'bow') {
                if (!$voided) {
                    $damage[] = $slot->item->getTotalDamage();
                } else {
                    $damage[] =  $slot->item->base_damage;
                }
            }
        }

        $damage = $this->damageModifiers(array_sum($damage), $voided);

        return $this->calculateWeaponDamage($damage, $voided);
    }

    public function damageModifiers(int $damage, bool $voided): int {
        if ($this->character->classType()->isFighter()) {
            if ($voided) {
                $statIncrease = $this->character->str * .10;
            } else {
                $statIncrease = $this->statMod('str') * 0.10;
            }

            $damage += $statIncrease;
        } else if($this->character->classType()->isThief() || $this->character->classType()->isRanger()) {
            if ($voided) {
                $statIncrease = $this->character->dex * .05;
            } else {
                $statIncrease = $this->statMod('dex') * 0.05;
            }

            $damage += $statIncrease;
        }

        return ceil($damage);
    }

    public function calculateWeaponDamage(int|float $damage, bool $voided = false): int|float {
        if ($damage === 0) {
            $damage = $voided ? $this->character->{$this->character->damage_stat} : $this->statMod($this->character->damage_stat);

            if ($this->character->classType()->isFighter()) {
                $damage = $damage * 0.05;
            } else {
                $damage = $damage * 0.02;
            }
        }

        $skills = $this->character->skills->filter(function($skill) {
            return $skill->baseSkill->base_damage_mod_bonus_per_level > 0.0;
        });

        foreach ($skills as $skill) {
            $damage += $damage * $skill->base_damage_mod;
        }

        return ceil($damage);
    }

    public function getSpellDamage(bool $voided = false): int {
        $damage = 0;

        $bonus = $this->hereticSpellDamageBonus($this->character);

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'spell-damage') {
                if (!$voided) {
                    $damage += $slot->item->getTotalDamage();
                } else {
                    $damage += $slot->item->base_damage;
                }
            }
        }

        $damage = $this->calculateClassSpellDamage($damage, $voided);

        return $damage + $damage * $bonus;
    }

    public function calculateClassSpellDamage(int|float $damage, bool $voided = false): float|int {
        if ($damage === 0) {
            $classType = $this->character->classType();

            if ($classType->isHeretic()) {
                if (!$voided) {
                    $damage = $this->statMod('int') * 0.2;
                } else {
                    $damage += $this->character->int * 0.02;
                }
            }
        } else if ($this->character->classType()->isHeretic()) {
            if ($voided) {
                $damage += $this->character->int * 0.30;
            } else {
                $damage += $this->statMod('int') * 0.30;
            }
        }

        return $damage;
    }

    protected function getArtifactDamage(bool $voided = false): int {
        $damage = 0;

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'artifact') {
                if ($damage === 0) {
                    $damage += $slot->item->getTotalDamage();
                } else {
                    $damage += ceil($slot->item->getTotalDamage() / 2);
                }
            }
        }

        return $damage;
    }

    protected function getRingDamage(bool $voided = false): int {
        $damage = [];

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'ring') {
                if (!$voided) {
                    $damage[] = $slot->item->getTotalDamage();
                } else {
                    $damage[] = $slot->item->base_damage;
                }
            }
        }

        if (!empty($damage)) {
            return max($damage);
        }

        return 0;
    }

    protected function getDefence(bool $voided = false): int {
        $defence = 0;

        foreach ($this->fetchInventory() as $slot) {
            if (!$voided) {
                $defence += $slot->item->getTotalDefence();
            } else {
                $defence += $slot->item->base_ac;
            }
        }

        if ($defence !== 10) {
            return $defence / 6;
        }

        return $defence;
    }

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
     * Fetch the appropriate inventory.
     *
     * Either return the current inventory, by default, if not empty or
     * return the inventory set that is currently equipped.
     *
     * Players cannot have both equipped at the same time.
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

    protected function fetchModdedStat(string $stat, Item $item): float {
        $staMod          = $item->{$stat . '_mod'};
        $totalPercentage = !is_null($staMod) ? $staMod : 0.0;

        if (!is_null($item->itemPrefix)) {
            $prefixMod        = $item->itemPrefix->{$stat . '_mod'};
            $totalPercentage += !is_null($prefixMod) ? $prefixMod : 0.0;
        }

        if (!is_null($item->itemSuffix)) {
            $suffixMod        = $item->itemSuffix->{$stat . '_mod'};
            $totalPercentage += !is_null($suffixMod) ? $suffixMod : 0.0;
        }

        return  $totalPercentage;
    }
}
