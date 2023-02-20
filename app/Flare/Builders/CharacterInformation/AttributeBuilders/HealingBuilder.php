<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;


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

    public function buildHealing(float $damageStat, bool $voided = false, string $position = 'both'): float {
        $class = $this->character->class;

        if ($class->type()->isHealer()) {
            $baseDamage = $damageStat * 0.05;
            $baseDamage = max($baseDamage, 5);
        } else {
            $baseDamage = 0;
        }

        $itemDamage      = $this->getHealingFromItems('spell-healing', $position);

        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class,'healing')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_healing');
        }

        $additionalBonus = 0.0;

        if ($this->character->class->type()->isArcaneAlchemist()) {
            $additionalBonus = $damageStat * 0.10;
        }

        $totalDamage = $baseDamage + $itemDamage;

        if ($voided) {
            return $totalDamage + $totalDamage * ($skillPercentage + $additionalBonus);
        }

        $affixPercentage = $this->getAttributeBonusFromAllItemAffixes('base_healing');

        $healingMasteryBonus = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellHealing($position);

        $healing = $totalDamage + $totalDamage * ($skillPercentage + $affixPercentage + $healingMasteryBonus + $additionalBonus);

        if ($this->character->class->type()->isAlcoholic()) {
            return $healing - ($healing * 0.50);
        }

        return $healing;
    }
}
