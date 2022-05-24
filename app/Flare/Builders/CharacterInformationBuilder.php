<?php

namespace App\Flare\Builders;

use App\Flare\Builders\Character\AttackDetails\CharacterAttackInformation;
use App\Flare\Builders\Character\BaseCharacterInfo;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Map;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Support\Collection;

class CharacterInformationBuilder {

    /**
     * @var BaseCharacterInfo $baseCharacterInfo
     */
    private $baseCharacterInfo;

    /**
     * @var CharacterAttackInformation $characterAttackInformation
     */
    private $characterAttackInformation;

    /**
     * @var Character $character
     */
    private $character;

    private $weaponInfo;

    /**
     * @param BaseCharacterInfo $baseCharacterInfo
     * @param CharacterAttackInformation $characterAttackInformation
     */
    public function __construct(BaseCharacterInfo $baseCharacterInfo, CharacterAttackInformation $characterAttackInformation) {
        $this->baseCharacterInfo          = $baseCharacterInfo;
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

        $this->characterAttackInformation = $this->characterAttackInformation->setCharacterInformationBuilder($this);
        $this->weaponInfo                 = $this->characterAttackInformation->getCharacterDamageInformation()->getWeaponInformation()->setCharacterInformation($this->baseCharacterInfo);

        return $this;
    }

    /**
     * Get the character.
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character;
    }

    /**
     * Get the BaseCharacterInfo instance.
     *
     * @return BaseCharacterInfo
     */
    public function getBaseCharacterInfo(): BaseCharacterInfo {
        return $this->baseCharacterInfo;
    }

    /**
     * Get the characters total stat mode for a stat
     *
     * Applies all bonuses to that stat based on equipped items in the
     * inventory assuming the user has anything equipped at all.
     *
     * @param string $stat
     * @return float
     */
    public function statMod(string $stat): float {
        return $this->baseCharacterInfo->statMod($this->character, $stat);
    }

    /**
     * Gets a specific skill based on name.
     *
     * @param string $skillName
     * @return float
     */
    public function getSkill(string $skillName): float {
        return $this->baseCharacterInfo->getSkill($this->character, $skillName);
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
                    ->calculateAttributeValue('class_bonus');
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
     * Get the entrancing chance.
     *
     * @return float
     */
    public function getEntrancedChance(): float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->calculateAttributeValue('entranced_chance');
    }

    /**
     * Get the best skill reduction amount.
     *
     * @return float
     */
    public function getBestSkillReduction() : float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->calculateAttributeValue('skill_reduction');
    }

    /**
     * Get the best resistance reduction amount.
     *
     * @return float
     */
    public function getBestResistanceReduction() : float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->calculateAttributeValue('resistance_reduction');
    }

    /**
     * Returns a collection of single stat reduction affixes.
     *
     * @return Collection
     */
    public function findSuffixStatReductionAffixes(): Collection {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->findSuffixStatReductionAffixes();
    }

    /**
     * Builds Total Attack.
     *
     * @return int
     * @throws \Exception
     */
    public function buildTotalAttack(): int {
        return $this->getTotalWeaponDamage() + $this->getTotalSpellDamage() + $this->getTotalRingDamage();
    }

    /**
     * Build the defence
     *
     * Fetches the defence based off a base of ten plus the equipment, skills and other
     * bonuses.
     *
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function buildDefence(bool $voided = false): int {
        return $this->baseCharacterInfo->buildDefence($this->character, $voided);
    }


    /**
     * Build heal for
     *
     * Fetches the total healing amount based on skills and equipment.
     *
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function buildHealFor(bool $voided = false): int {
        return $this->character->getHeathInformation()->buildHealFor($voided);
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
     * Can your affixes be resisted at all?
     *
     * If you have the quest item that has the AFFIXES_IRRESISTIBLE
     * effect, then you cannot be resisted for affixes.
     *
     * @return bool
     */
    public function canAffixesBeResisted(): bool {
        if ($this->character->map->gameMap->mapType()->isHell() || $this->character->map->gameMap->mapType()->isPurgatory()) {
            return false;
        }

        return $this->character->inventory->slots->filter(function($slot) {
            return ($slot->item->type === 'quest') && ($slot->item->effect === ItemEffectsValue::AFFIXES_IRRESISTIBLE);
        })->isNotEmpty();
    }

    /**
     * Do we have any affixes that are considered irresistible?
     *
     * @return bool
     */
    public function hasIrresistibleAffix(): bool {
        return $this->characterAttackInformation->setCharacter($this->character)->hasAffixesWithType('irresistible_damage');
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
        return $this->characterAttackInformation
            ->setCharacter($this->character)
            ->getTotalAffixDamage($canStack);
    }

    /**
     * Get total weapon damage.
     *
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function getTotalWeaponDamage(bool $voided = false): int {
        return $this->weaponInfo->getWeaponDamage($this->character, $voided);
    }

    /**
     * Get the total Spell Damage
     *
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function getTotalSpellDamage(bool $voided = false): int {
        return $this->characterAttackInformation->getCharacterDamageInformation()->getSpellDamage($this->character, $voided);
    }

    /**
     * Gets the total ring damage.
     *
     * @param bool $voided
     * @return int
     */
    public function getTotalRingDamage(bool $voided = false): int {
        return $this->characterAttackInformation->getCharacterDamageInformation()->getRingDamage($this->character, $voided);
    }

    /**
     * Get the total deduction for the type.
     *
     * @param string $type
     * @return float
     */
    public function getTotalDeduction(string $type): float {
        return $this->getDeduction($type);
    }

    /**
     * Gets associated damage modifiers.
     *
     * @param int $damage
     * @param bool $isVoided
     * @return int
     * @throws \Exception
     */
    public function damageModifiers(int $damage, bool $isVoided = false): int {
        return $this->characterAttackInformation->getCharacterDamageInformation()->getWeaponInformation()->damageModifiers($this->character, $damage, $isVoided);
    }

    /**
     * Calculates Weapon Damage.
     *
     * @param int|float $damage
     * @param bool $voided
     * @return int|float
     */
    public function calculateWeaponDamage(int|float $damage, bool $voided = false): int|float {
        return $this->characterAttackInformation->getCharacterDamageInformation()->getWeaponInformation()->calculateWeaponDamage($this->character, $damage, $voided);
    }

    /**
     * Get the total devouring light amount.
     *
     * @return float
     */
    public function getDevouringLight(): float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->fetchVoidanceAmount('devouring_light');
    }

    /**
     * Get the total devouring darkness amount.
     *
     * @return float
     */
    public function getDevouringDarkness(): float {
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->fetchVoidanceAmount('devouring_darkness');
    }

    /**
     * Get total annulment
     *
     * @return float
     */
    public function getTotalAnnulment(): float {
        return $this->getTotalDeduction('artifact_annulment');
    }

    /**
     * Fetch the resurrection chance;
     *
     * @return float
     * @throws \Exception
     */
    public function fetchResurrectionChance(): float {
        return $this->character->getHeathInformation()->fetchResurrectionChance();
    }

    /**
     * Build total health
     *
     * Build the characters health based off equipment, plus the characters health and
     * a base of 10.
     *
     * @param bool $voided
     * @return int
     */
    public function buildHealth(bool $voided = false): int {
        return $this->baseCharacterInfo->buildHealth($this->character, $voided);
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
            ->getAffixInformation()
            ->findLifeStealingAffixes($canStack);
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
     * Calculate the players spell damage.
     *
     * @param int $spellDamage
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function calculateClassSpellDamage(int $spellDamage, bool $voided = false): int {
        return $this->characterAttackInformation->getCharacterDamageInformation()->getDamageSpellInformation()->calculateClassSpellDamage($this->character, $spellDamage, $voided);
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
        return $this->characterAttackInformation
                    ->setCharacter($this->character)
                    ->fetchInventory();
    }

    /**
     * @param string $type
     * @return float
     */
    protected function getDeduction(string $type): float {
        $itemsDeduction = $this->fetchInventory()->filter(function ($slot) {
            return $slot->item->type === 'ring' && $slot->equipped;
        })->pluck('item.' . $type)->toArray();

        if (empty($itemsDeduction)) {
            return 0.0;
        }

        return max($itemsDeduction);
    }
}
