<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Traits\ClassBasedBonuses;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ItemUsabilityType;
use Illuminate\Support\Collection;

class CharacterInformationBuilder {

    use ClassBasedBonuses;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Illuminate\Support\Collection $inventory
     */
    private $inventory;

    /**
     * Set the character and fetch it's inventory.
     *
     * @param Character $character
     * @return CharactrInformationBuilder
     */
    public function setCharacter(Character $character): CharacterInformationBuilder {
        $this->character = $character;

        $this->inventory = $character->inventory->slots->where('equipped', true);

        return $this;
    }

    /**
     * Get the characters total stat mode for a stat
     *
     * Applies all bonuses to that stat based on equipped items in the
     * inventory assuming the user has anything equipped at all.
     *
     * @param Character $character
     * @return float
     */
    public function statMod(string $stat): float {
        $base = $this->character->{$stat};

        $equipped = $this->fetchInventory()->filter(function($slot) {
            return $slot->equipped;
        });

        if ($equipped->isEmpty()) {
            return $base;
        }

        foreach ($equipped as $slot) {
            $base += $base * $this->fetchModdedStat($stat, $slot->item);
        }

        if ($this->character->boons->isNotEmpty()) {
            $boons = $this->character->boons()->where('type', ItemUsabilityType::STAT_INCREASE)->get();

            if ($boons->isNotEmpty()) {
                $sum = $boons->sum('stat_bonus');

                $base += $base + $base * $sum;
            }
        }

        return $base;
    }

    /**
     * Build the attack
     *
     * Fetches the damage stat with all modifications and applies all skill bonuses.
     *
     * @return int
     */
    public function buildAttack(): int {

        $characterDamageStat = $this->statMod($this->character->damage_stat);
        $classBonuses        = $this->getFightersDamageBonus($this->character) +
            $this->prophetDamageBonus($this->character) +
            $this->getThievesDamageBonus($this->character) +
            $this->getVampiresDamageBonus($this->character) +
            $this->getRangersDamageBonus($this->character);

        $characterDamageStat = $characterDamageStat + $characterDamageStat * $this->fetchSkillAttackMod();

        $totalAttack = $this->getWeaponDamage();

        return round($characterDamageStat + ($totalAttack + $totalAttack * $classBonuses));
    }

    /**
     * Builds Total Attack.
     *
     * @return int
     * @throws \Exception
     */
    public function buildTotalAttack(): int {

        $characterDamageStat = $this->statMod($this->character->damage_stat);
        $classBonuses        = $this->getFightersDamageBonus($this->character) +
            $this->prophetDamageBonus($this->character) +
            $this->getThievesDamageBonus($this->character) +
            $this->getVampiresDamageBonus($this->character) +
            $this->getRangersDamageBonus($this->character);

        $characterDamageStat = $characterDamageStat + $characterDamageStat * $this->fetchSkillAttackMod();

        $totalAttack = $this->getWeaponDamage() + $this->getSpellDamage() + $this->getTotalArtifactDamage() + $this->getTotalRingDamage();

        return round($characterDamageStat + ($totalAttack + $totalAttack * $classBonuses));
    }

    /**
     * Build the defence
     *
     * Fetches the defence based off a base of ten plus the equipment, skills and other
     * bonuses.
     *
     * @return int
     */
    public function buildDefence(): int {
        return round((10 + $this->getDefence()) * (1 + $this->fetchSkillACMod() + $this->getFightersDefence($this->character)));
    }

    /**
     * Build the heal for
     *
     * Fetches the total healing amount based on skills and equipment.
     *
     * @return int
     * @throws \Exception
     */
    public function buildHealFor(): int {
        $classBonus    = $this->prophetHealingBonus($this->character) + $this->getVampiresHealingBonus($this->character);

        $classType     = new CharacterClassValue($this->character->class->name);

        $healingAmount = $this->fetchHealingAmount();
        $dmgStat       = $this->character->class->damage_stat;

        if ($classType->isVampire()) {
            $healingAmount += $this->character->{$dmgStat} - $this->character->{$dmgStat} * .025;
        }

        if ($classType->isProphet()) {
            $hasHealingSpells = $this->prophetHasHealingSpells($this->character);

            if ($hasHealingSpells) {
                $healingAmount += $this->character->{$dmgStat} * .025;
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

        if ($classType->isVampire() || $classType->isProphet()) {
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
    public function buildHealth(): int {

        if ($this->character->is_dead) {
            return 0;
        }

        $baseHealth = $this->character->dur + 10;

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
            return ((!is_null($slot->item->itemPrefix)) || (!is_null($slot->item->itemSuffix))) && $slot->equipped;
        })->isNotEmpty();
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

    /**
     * Get the total Spell Damage
     *
     * @return int
     */
    public function getTotalSpellDamage(): int {
        return $this->getSpellDamage();
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
    public function getTotalRingDamage(): int {
        return $this->getRingDamage();
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

    protected function getSpellEvasion(): float {
        $skillSpellEvasion = 0.0;

        $skill = $this->character->skills->filter(function($skill) {
            return $skill->type()->isSpellEvasion();
        })->first();

        if (!is_null($skill)) {
            $skillSpellEvasion = $skill->skill_bonus;
        }

        $itemsEvasion = $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'ring' && $slot->equipped;
        })->sum('item.spell_evasion');

        return $itemsEvasion + $skillSpellEvasion;
    }

    protected function getArtifactAnnulment(): float {
        $skillArtifactAnnulment = 0.0;

        $skill = $this->character->skills->filter(function($skill) {
            return $skill->type()->isArtifactAnnulment();
        })->first();

        if (!is_null($skill)) {
            $skillArtifactAnnulment = $skill->skill_bonus;
        }

        $itemsEvasion = $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'ring' && $slot->equipped;
        })->sum('item.artifact_annulment');

        return $itemsEvasion + $skillArtifactAnnulment;
    }

    protected function fetchSkillAttackMod(): float {
        $percentageBonus = 0.0;

        $skills = $this->character->skills->filter(function($skill) {
            return is_null($skill->baseSkill->game_class_id);
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

    protected function getWeaponDamage(): int {
        $damage = 0;

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'weapon') {
                $damage += $slot->item->getTotalDamage();
            }
        }

        return $damage;
    }

    protected function getSpellDamage(): int {
        $damage = 0;

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'spell-damage') {
                $damage += $slot->item->getTotalDamage();
            }
        }

        $bonus = $this->hereticSpellDamageBonus($this->character);

        if ($bonus < 2) {
            $bonus += 1;
        }

        return $damage * $bonus;
    }

    protected function getArtifactDamage(): int {
        $damage = 0;

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'artifact') {
                $damage += $slot->item->getTotalDamage();
            }
        }

        return $damage;
    }

    protected function getRingDamage(): int {
        $damage = 0;

        foreach ($this->fetchInventory() as $slot) {
            if ($slot->item->type === 'ring') {
                $damage += $slot->item->getTotalDamage();
            }
        }

        return $damage;
    }

    protected function getDefence(): int {
        $defence = 0;

        foreach ($this->fetchInventory() as $slot) {

            $defence += $slot->item->getTotalDefence();
        }

        if ($defence !== 10) {
            return $defence / 6;
        }

        return $defence;
    }

    protected function fetchHealingAmount(): int {
        $healFor = 0;

        foreach ($this->fetchInventory() as $slot) {
            $healFor += $slot->item->getTotalHealing();
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
    protected function fetchInventory(): Collection
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
