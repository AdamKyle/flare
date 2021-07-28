<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\ItemUsabilityType;

class CharacterInformationBuilder {

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

        $equipped = $this->inventory->filter(function($slot) {
            return $slot->equipped;
        });

        if ($equipped->isEmpty()) {
            return $base;
        }

        foreach ($equipped as $slot) {
            $percentageIncrease = $this->fetchModdedStat($stat, $slot->item);

            if ($percentageIncrease < 2) {
                $percentageIncrease = 1 + $percentageIncrease;
            }

            if ($percentageIncrease !== 0.0) {
                $base *= $percentageIncrease;
            }
        }

        if (!$this->character->boons->isEmpty()) {
            $boons = $this->character->boons()->where('type', ItemUsabilityType::STAT_INCREASE)->get();

            if ($boons->isNotEmpty()) {
                $sum = $boons->sum('stat_bonus');

                if ($sum < 1.0) {
                    $sum += 1;
                }

                $base *= $sum;
            }
        }

        return round($base);
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
        $characterDamageStat *= 1 + $this->fetchSkillAttackMod();

        $totalAttack = $this->getWeaponDamage();

        return round($characterDamageStat + $totalAttack);
    }

    public function buildTotalAttack(): int {
        $characterDamageStat = $this->statMod($this->character->damage_stat);
        $characterDamageStat *= 1 + $this->fetchSkillAttackMod();

        $totalAttack = $this->getWeaponDamage() + $this->getSpellDamage() + $this->getTotalArtifactDamage();

        return round($characterDamageStat + $totalAttack);
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
        return round((10 + $this->getDefence()) * (1 + $this->fetchSkillACMod()));
    }

    /**
     * Build the heal for
     *
     * Fetches the total healing amount based on skills and equipment.
     *
     * @return int
     */
    public function buildHealFor(): int {
        return round($this->fetchHealingAmount() * (1 + $this->fetchSkillHealingMod()));
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

        $totalPercentage = 1.0;
        $baseHealth      = $this->character->dur + 10;

        foreach ($this->character->inventory->slots as $slot) {
            if ($slot->equipped) {
                $percentage = $slot->item->getTotalPercentageForStat('dur');

                if ($percentage < 1) {
                    $percentage = 1 + $percentage;
                }

                $baseHealth *= $percentage;
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
        return $this->inventory->filter(function ($slot) {
            return $slot->item->type === 'artifact' && $slot->equipped;
        })->isNotEmpty();
    }

    /**
     * Does the character have any items with affixes?
     *
     * @return bool
     */
    public function hasAffixes(): bool {
        return $this->inventory->filter(function ($slot) {
            return ((!is_null($slot->item->itemPrefix)) || (!is_null($slot->item->itemSuffix))) && $slot->equipped;
        })->isNotEmpty();
    }

    /**
     * Does the character have any damage spells
     *
     * @return bool
     */
    public function hasDamageSpells(): bool {
        return $this->inventory->filter(function ($slot) {
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
     * Get total annulment
     *
     * @return float
     */
    public function getTotalAnnulment(): float {
        return  $this->character->getCharacterArtifactAnnulment();
    }

    /**
     * Get total spell evasion
     *
     * @return float
     */
    public function getTotalSpellEvasion(): float {
        return  $this->character->getCharacterSpellEvasion();
    }

    protected function fetchSkillAttackMod(): float {
        $percentageBonus = 0.0;

        foreach ($this->character->skills as $skill) {
            $percentageBonus += $skill->base_damage_mod + ($skill->level / 100);
        }

        return $percentageBonus;
    }

    protected function fetchSkillHealingMod(): float {
        $percentageBonus = 0.0;

        foreach ($this->character->skills as $skill) {
            $percentageBonus += $skill->base_healing_mod + ($skill->level / 100);
        }

        return $percentageBonus;
    }

    protected function fetchSkillACMod(): float {
        $percentageBonus = 0.0;

        foreach ($this->character->skills as $skill) {
            $percentageBonus += $skill->base_ac_mod + ($skill->level / 100);
        }

        return $percentageBonus;
    }

    protected function getWeaponDamage(): int {
        $damage = 0;

        foreach ($this->inventory as $slot) {
            if ($slot->type === 'weapon' || $slot->type === 'ring') {
                $damage += $slot->item->getTotalDamage();
            }
        }

        return $damage;
    }

    protected function getSpellDamage(): int {
        $damage = 0;

        foreach ($this->inventory as $slot) {
            if ($slot->item->type === 'spell-damage') {
                $damage += $slot->item->getTotalDamage();
            }
        }

        return $damage;
    }

    protected function getArtifactDamage(): int {
        $damage = 0;

        foreach ($this->inventory as $slot) {
            if ($slot->item->type === 'artifact') {
                $damage += $slot->item->getTotalDamage();
            }
        }

        return $damage;
    }

    protected function getDefence(): int {
        $defence = 0;

        foreach ($this->inventory as $slot) {

            $defence += $slot->item->getTotalDefence();
        }

        if ($defence !== 10) {
            return $defence / 8;
        }

        return $defence;
    }

    protected function fetchHealingAmount(): int {
        $healFor = 0;

        foreach ($this->inventory as $slot) {
            $healFor += $slot->item->getTotalHealing();
        }

        return $healFor;
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
