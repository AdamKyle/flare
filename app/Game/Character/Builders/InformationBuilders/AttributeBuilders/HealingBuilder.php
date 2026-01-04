<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Flare\Models\Character;
use Illuminate\Support\Collection;

class HealingBuilder extends BaseAttribute
{
    private ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder;

    public function __construct(ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder)
    {
        $this->classRanksWeaponMasteriesBuilder = $classRanksWeaponMasteriesBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(Character $character, Collection $skills, ?Collection $inventory): void
    {
        parent::initialize($character, $skills, $inventory);

        $this->classRanksWeaponMasteriesBuilder->initialize($this->character, $this->skills, $this->inventory);
    }

    /**
     * Build the healing.
     */
    public function buildHealing(bool $voided = false, string $position = 'both'): float
    {
        $class = $this->character->class;

        $itemHealing = $this->getHealingFromItems('spell-healing', $position);

        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class, 'healing')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_healing');
        }

        if ($voided) {
            return $itemHealing + $itemHealing * ($skillPercentage);
        }

        $affixPercentage = $this->getAttributeBonusFromAllItemAffixes('base_healing');

        $healingMasteryBonus = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellHealing($position);

        $healing = $itemHealing + $itemHealing * ($skillPercentage + $affixPercentage + $healingMasteryBonus);

        if ($this->character->class->type()->isAlcoholic()) {
            return $healing - ($healing * 0.50);
        }

        return $healing;
    }

    public function getHealingBuilder(bool $isVoided): array
    {
        $details = [];

        $details['base_healing'] = $this->getHealingFromItems('spell-healing', 'both');
        $details['skills_effecting_healing'] = null;

        if ($this->shouldIncludeSkillDamage($this->character->class, 'healing')) {
            $details['skills_effecting_healing'] = $this->fetchBaseAttributeFromSkillsDetails('base_healing');
        }

        $details['masteries'] = [];

        $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition('spell-healing', 'spell-one');
        $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition('spell-healing', 'spell-two');

        $details['masteries'] = array_filter($details['masteries']);

        return $details;
    }
}
