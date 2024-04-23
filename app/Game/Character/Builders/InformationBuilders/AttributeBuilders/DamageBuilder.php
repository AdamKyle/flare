<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;


use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\WeaponTypes;
use Exception;
use Illuminate\Support\Collection;

class DamageBuilder extends BaseAttribute {

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
     * Build weapon damage.
     *
     * @param float $damageStat
     * @param bool $voided
     * @param string $position
     * @return float
     * @throws Exception
     */
    public function buildWeaponDamage(float $damageStat, bool $voided = false, string $position = 'both'): float {
        $class      = $this->character->class;
        $baseDamage = 0;

        if ($this->character->class->type()->isFighter()) {
            $baseDamage = $damageStat * 0.15;
        } else if ($this->character->class->type()->isArcaneAlchemist()) {
            $hasStaveEquipped = $this->inventory->filter(function ($slot) {
                return $slot->item->type === WeaponTypes::STAVE;
            })->isNotEmpty();

            if ($hasStaveEquipped) {
                $baseDamage = $damageStat * 0.15;
            } else {
                $baseDamage = $damageStat * 0.05;
            }
        } else {
            $baseDamage = $damageStat * 0.05;
        }

        $itemDamage      = $this->getDamageFromWeapons($position);
        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class, 'weapon')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_damage');
        }

        $totalDamage = $baseDamage + $itemDamage;

        if ($voided) {
            return $totalDamage + $totalDamage * $skillPercentage;
        }

        $affixPercentage         = $this->getAttributeBonusFromAllItemAffixes('base_damage');
        $weaponMasteryPercentage = $this->classRanksWeaponMasteriesBuilder->determineBonusForWeapon($position);

        $damage = $totalDamage + $totalDamage * ($skillPercentage + $affixPercentage + $weaponMasteryPercentage);

        if ($this->character->classType()->isAlcoholic() && $itemDamage > 0) {
            return $damage - ($damage * 0.25);
        }

        return $damage;
    }

    public function buildWeaponDamageBreakDown(float $damageStat, bool $voided): array {
        $details = [];

        if ($this->character->class->type()->isFighter()) {
            $baseDamage = $damageStat * 0.15;

            $details['base_damage'] = number_format($baseDamage);
            $details['percentage_of_stat'] = 0.15;

        } else if ($this->character->class->type()->isArcaneAlchemist()) {
            $hasStaveEquipped = $this->inventory->filter(function ($slot) {
                return $slot->item->type === WeaponTypes::STAVE;
            })->isNotEmpty();

            if ($hasStaveEquipped) {
                $baseDamage = $damageStat * 0.15;

                $details['base_damage'] = number_format($baseDamage);
                $details['percentage_of_stat'] = 0.15;
            } else {
                $baseDamage = $damageStat * 0.05;

                $details['base_damage'] = number_format($baseDamage);
                $details['percentage_of_stat'] = 0.05;
            }
        } else {
            $baseDamage = $damageStat * 0.05;

            $details['base_damage'] = number_format($baseDamage);
            $details['percentage_of_stat'] = 0.05;
        }

        $details['skills_effecting_damage'] = null;

        if ($this->shouldIncludeSkillDamage($this->character->class, 'weapon')) {
            $details['skills_effecting_damage'] = $this->fetchBaseAttributeFromSkillsDetails('base_damage');
        }

        $details['attached_affixes'] = $this->getAttributeBonusFromAllItemAffixesDetails('base_damage', $voided);

        $details['masteries'] = [];

        $slots = $this->inventory->filter(function ($slot) {
            return in_array($slot->item->type, [
                WeaponTypes::WEAPON,
                WeaponTypes::STAVE,
                WeaponTypes::SCRATCH_AWL,
                WeaponTypes::HAMMER,
                WeaponTypes::MACE,
                WeaponTypes::GUN,
                WeaponTypes::FAN,
                WeaponTypes::BOW,
            ]);
        });

        foreach ($slots as $slot) {
            $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition($slot->item->type, $slot->position);
        }

        $details['masteries'] = array_filter($details['masteries']);

        return $details;
    }



    /**
     * Build ring damage.
     *
     * @return int
     */
    public function buildRingDamage(): int {
        return $this->getDamageFromItems('ring', 'both');
    }

    /**
     * Build spell damage.
     *
     * @param bool $voided
     * @param string $position
     * @return float
     * @throws Exception
     */
    public function buildSpellDamage(bool $voided = false, string $position = 'both'): float {
        $class = $this->character->class;

        $itemDamage      = $this->getDamageFromItems('spell-damage', $position);

        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class, 'spell')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_damage');
        }

        if ($voided) {
            return $itemDamage + $itemDamage * $skillPercentage;
        }

        $affixPercentage = $this->getAttributeBonusFromAllItemAffixes('base_damage');

        $spellMasteryPercentage = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellDamage($position);

        $damage = $itemDamage + $itemDamage * ($skillPercentage + $affixPercentage + $spellMasteryPercentage);

        return $damage;
    }

    public function buildSpellDamageBreakDownDetails(bool $voided): array {
        $details = [];

        $details['attached_affixes'] = $this->getAttributeBonusFromAllItemAffixesDetails('base_damage', $voided, 'spell-damage');
        $details['skills_effecting_damage'] = null;
        $details['base_damage'] = number_format($this->getDamageFromItems('spell-damage', 'both'));

        if ($this->shouldIncludeSkillDamage($this->character->class, 'spell')) {
            $details['skills_effecting_damage'] = $this->fetchBaseAttributeFromSkillsDetails('base_damage');
        }

        $details['masteries'] = [];

        $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition('spell-damage', 'spell-one');
        $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition('spell-damage', 'spell-two');

        $details['masteries'] = array_filter($details['masteries']);

        return $details;
    }

    public function buildRingDamageBreakDown(): array {
        $details['attached_affixes'] = $this->getAttributeBonusFromAllItemAffixesDetails('base_damage', false, 'ring');
        $details['base_damage'] = $this->getDamageFromItems('ring', 'both');
        $details['skills_effecting_damage'] = null;
        $details['masteries'] = [];

        return $details;
    }

    /**
     * Build stacking affix damage.
     *
     * @param bool $voided
     * @return int
     */
    public function buildAffixStackingDamage(bool $voided = false): float {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $itemSuffix = $this->inventory->where('item.itemSuffix.damage_can_stack', true)->sum('item.itemSuffix.damage_amount');
        $itemPrefix = $this->inventory->where('item.itemPrefix.damage_can_stack', true)->sum('item.itemPrefix.damage_amount');

        return $itemSuffix + $itemPrefix;
    }

    /**
     * Build affix non stacking damage.
     *
     * @param bool $voided
     * @return int
     */
    public function buildAffixNonStackingDamage(bool $voided = false): float {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $itemSuffix = $this->inventory->where('item.itemSuffix.damage_can_stack', false)->sum('item.itemSuffix.damage_amount');
        $itemPrefix = $this->inventory->where('item.itemPrefix.damage_can_stack', false)->sum('item.itemPrefix.damage_amount');

        $amounts = array_filter([$itemPrefix, $itemSuffix]);

        if (empty($amounts)) {
            return 0.0;
        }

        return max($amounts);
    }

    /**
     * Build life stealing.
     *
     * @param bool $voided
     * @return float
     */
    public function buildLifeStealingDamage(bool $voided = false): float {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $class   = $this->character->class;
        $gameMap = $this->character->map->gameMap;

        if ($class->type()->isVampire()) {
            $itemSuffix = $this->inventory->sum('item.itemSuffix.steal_life_amount');
            $itemPrefix = $this->inventory->sum('item.itemPrefix.steal_life_amount');

            $lifeStealAmount  = $itemSuffix + $itemPrefix;


            if ($lifeStealAmount >= 1) {
                $lifeStealAmount =  0.99;
            }

            $lifeStealAmount = $this->getLifeStealAfterPlaneReductions($gameMap, $lifeStealAmount);

            return max($lifeStealAmount, 0);
        }

        $lifeStealAmounts = [
            $this->inventory->max('item.itemSuffix.steal_life_amount'),
            $this->inventory->max('item.itemPrefix.steal_life_amount'),
        ];

        $lifeStealAmounts = array_filter($lifeStealAmounts);

        if (empty($lifeStealAmounts)) {
            return 0;
        }

        $lifeStealAmounts = $lifeStealAmounts >= 1 ? .99 : $lifeStealAmounts;

        $lifeStealAmount = $this->getLifeStealAfterPlaneReductions($gameMap, $lifeStealAmounts);

        return max($lifeStealAmount, 0);
    }

    protected function getLifeStealAfterPlaneReductions(GameMap $gameMap, float $lifeSteal): float {

        if ($gameMap->mapType()->isHell()) {
            return $lifeSteal - ($lifeSteal * .10);
        }

        if ($gameMap->mapType()->isPurgatory()) {
            return $lifeSteal - ($lifeSteal * .20);
        }

        if ($gameMap->mapType()->isTwistedMemories()) {
            return $lifeSteal - ($lifeSteal * .25);
        }

        $hasPurgatoryItem = $this->character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::PURGATORY;
        })->first();

        if (!is_null($hasPurgatoryItem)) {
            if ($gameMap->mapType()->isTheIcePlane()) {
                return $lifeSteal - ($lifeSteal * .20);
            }

            if ($gameMap->mapType()->isDelusionalMemories()) {
                return $lifeSteal - ($lifeSteal * .30);
            }
        }

        return $lifeSteal;
    }
}
