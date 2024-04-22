<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;


use App\Flare\Models\Character;
use Illuminate\Support\Collection;

class HealingBuilder extends BaseAttribute {

    /**
     * @var ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder
     */
    private ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder;

    /**
     * @param ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder
     */
    public function __construct(ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder) {
        $this->classRanksWeaponMasteriesBuilder = $classRanksWeaponMasteriesBuilder;
    }

    /**
     * @inheritDoc
     * @param Character $character
     * @param Collection $skills
     * @param Collection|null $inventory
     * @return void
     */
    public function initialize(Character $character, Collection $skills, ?Collection $inventory): void {
        parent::initialize($character, $skills, $inventory);

        $this->classRanksWeaponMasteriesBuilder->initialize($this->character, $this->skills, $this->inventory);
    }

    /**
     * Build the healing.
     *
     * @param bool $voided
     * @param string $position
     * @return float
     */
    public function buildHealing(bool $voided = false, string $position = 'both'): float {
        $class = $this->character->class;

        $itemHealing      = $this->getHealingFromItems('spell-healing', $position);

        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class,'healing')) {
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

    public function getHealingBuilder(bool $isVoided): array {
        $details = [];

        $details['base_damage'] = $this->getHealingFromItems('spell-healing', 'both');
        $details['attached_affixes'] = $this->getAttributeBonusFromAllItemAffixesDetails('base_healing', $isVoided, 'spell-healing');
        $details['skills_effecting_damage'] = null;

        if ($this->shouldIncludeSkillDamage($this->character->class, 'healing')) {
            $details['skills_effecting_damage'] = $this->fetchBaseAttributeFromSkillsDetails('base_healing');
        }

        return $details;
    }
}
