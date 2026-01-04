<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\WeaponTypes;
use Exception;
use Illuminate\Support\Collection;

class DamageBuilder extends BaseAttribute
{
    private ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder;

    public function __construct(ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder)
    {
        $this->classRanksWeaponMasteriesBuilder = $classRanksWeaponMasteriesBuilder;
    }

    public function initialize(Character $character, Collection $skills, ?Collection $inventory): void
    {
        parent::initialize($character, $skills, $inventory);

        $this->classRanksWeaponMasteriesBuilder->initialize($this->character, $this->skills, $this->inventory);
    }

    /**
     * Build weapon damage.
     *
     * @throws Exception
     */
    public function buildWeaponDamage(float $damageStat, bool $voided = false, string $position = 'both'): float
    {
        $class = $this->character->class;

        $baseDamage = match (true) {
            $this->character->class->type()->isThief() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::DAGGER->value, 2, ItemType::BOW->value),
            $this->character->class->type()->isMerchant() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::STAVE->value, 1, ItemType::BOW->value),
            $this->character->class->type()->isFighter() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::SWORD->value, 2),
            $this->character->class->type()->isHeretic() || $this->character->class->type()->isArcaneAlchemist() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::STAVE->value),
            $this->character->class->type()->isBlackSmith() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::HAMMER->value),
            $this->character->class->type()->isCleric() || $this->character->class->type()->isProphet() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::MACE->value),
            $this->character->class->type()->isGunslinger() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::GUN->value, 2),
            $this->character->class->type()->isDancer() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::FAN->value),
            $this->character->class->type()->isBookBinder() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::SCRATCH_AWL->value),
            $this->character->class->type()->isRanger() => $this->buildBonusDamageForClassBasedOnWeaponTypeEquipped($damageStat, ItemType::BOW->value),
            default => ($damageStat * 0.05),
        };

        $itemDamage = $this->getDamageFromWeapons($position);
        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class, 'weapon')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_damage');
        }

        $totalDamage = $baseDamage + $itemDamage;

        if ($voided) {
            return $totalDamage + $totalDamage * $skillPercentage;
        }

        $affixPercentage = $this->getAttributeBonusFromAllItemAffixes('base_damage');
        $weaponMasteryPercentage = $this->classRanksWeaponMasteriesBuilder->determineBonusForWeapon($position);

        $damage = $totalDamage + $totalDamage * ($skillPercentage + $affixPercentage + $weaponMasteryPercentage);

        if ($this->character->classType()->isAlcoholic() && $itemDamage > 0) {
            return $damage - ($damage * 0.25);
        }

        if ($damage < 5) {
            return 8;
        }

        return $damage;
    }

    public function buildWeaponDamageBreakDown(float $damageStat, bool $voided): array
    {
        $details = [];

        if ($this->character->class->type()->isFighter()) {
            $baseDamage = $damageStat * 0.08;

            $details['base_damage'] = number_format($baseDamage);

            $details['percentage_of_stat_used'] = 0.08;
        } elseif ($this->character->class->type()->isArcaneAlchemist()) {
            $hasStaveEquipped = $this->inventory->filter(function ($slot) {
                return $slot->item->type === WeaponTypes::STAVE;
            })->isNotEmpty();

            if ($hasStaveEquipped) {
                $baseDamage = $damageStat * 0.15;

                $details['base_damage'] = number_format($baseDamage);
                $details['percentage_of_stat_used'] = 0.15;
            } else {
                $baseDamage = $damageStat * 0.05;

                $details['base_damage'] = number_format($baseDamage);
                $details['percentage_of_stat_used'] = 0.05;
            }
        } else {
            $baseDamage = $damageStat * 0.05;

            $details['base_damage'] = number_format($baseDamage);
            $details['percentage_of_stat_used'] = 0.05;
        }

        $details['skills_effecting_damage'] = null;

        if ($this->shouldIncludeSkillDamage($this->character->class, 'weapon')) {
            $details['skills_effecting_damage'] = $this->fetchBaseAttributeFromSkillsDetails('base_damage');
        }

        $details['masteries'] = [];

        if (is_null($this->inventory)) {
            return $details;
        }

        $slots = $this->inventory->filter(function ($slot) {
            return in_array($slot->item->type, ItemType::validWeapons());
        });

        foreach ($slots as $slot) {

            if (is_null($slot->item->type) || is_null($slot->position)) {
                dd($slot->item);
            }

            $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition($slot->item->type, $slot->position);
        }

        $details['masteries'] = array_filter($details['masteries']);

        return $details;
    }

    /**
     * Build ring damage.
     */
    public function buildRingDamage(): int
    {
        return $this->getDamageFromItems('ring', 'both');
    }

    /**
     * Build spell damage.
     *
     * @throws Exception
     */
    public function buildSpellDamage(bool $voided = false, string $position = 'both'): float
    {
        $class = $this->character->class;

        $itemDamage = $this->getDamageFromItems(ItemType::SPELL_DAMAGE->value, $position);

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

    public function buildSpellDamageBreakDownDetails(bool $voided): array
    {
        $details = [];

        $details['skills_effecting_damage'] = null;
        $details['base_damage'] = $this->getDamageFromItems(ItemType::SPELL_DAMAGE->value, 'both');

        if ($this->shouldIncludeSkillDamage($this->character->class, 'spell')) {
            $details['skills_effecting_damage'] = $this->fetchBaseAttributeFromSkillsDetails('base_damage');
        }

        $details['masteries'] = [];

        $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition(ItemType::SPELL_DAMAGE->value, 'spell-one');
        $details['masteries'][] = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition(ItemType::SPELL_DAMAGE->value, 'spell-two');

        $details['masteries'] = array_filter($details['masteries']);

        return $details;
    }

    public function buildRingDamageBreakDown(): array
    {
        $details['attached_affixes'] = $this->getAttributeBonusFromAllItemAffixesDetails('base_damage', false, ItemType::RING->value);
        $details['base_damage'] = $this->getDamageFromItems('ring', 'both');
        $details['skills_effecting_damage'] = null;
        $details['masteries'] = [];

        return $details;
    }

    /**
     * Build stacking affix damage.
     */
    public function buildAffixStackingDamage(bool $voided = false): float
    {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $itemSuffix = $this->inventory->where('item.itemSuffix.damage_can_stack', true)->sum('item.itemSuffix.damage_amount');
        $itemPrefix = $this->inventory->where('item.itemPrefix.damage_can_stack', true)->sum('item.itemPrefix.damage_amount');

        return $itemSuffix + $itemPrefix;
    }

    /**
     * Build affix non stacking damage.
     */
    public function buildAffixNonStackingDamage(bool $voided = false): float
    {

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
     */
    public function buildLifeStealingDamage(bool $voided = false): float
    {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $class = $this->character->class;
        $gameMap = $this->character->map->gameMap;

        if ($class->type()->isVampire()) {
            $itemSuffix = $this->inventory->sum('item.itemSuffix.steal_life_amount');
            $itemPrefix = $this->inventory->sum('item.itemPrefix.steal_life_amount');

            $lifeStealAmount = $itemSuffix + $itemPrefix;

            if ($lifeStealAmount >= 1) {
                $lifeStealAmount = 0.99;
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

        // Merge in the default smallest value as doing min(x, 0.5) gives unexpected results, this way we always take the smallest amount.
        $lifeStealAmounts = min(array_merge($lifeStealAmounts, [0.5]));

        $lifeStealAmount = $this->getLifeStealAfterPlaneReductions($gameMap, $lifeStealAmounts);

        return max($lifeStealAmount, 0);
    }

    private function getLifeStealAfterPlaneReductions(GameMap $gameMap, float $lifeSteal): float
    {

        if ($gameMap->mapType()->isHell()) {
            return $lifeSteal - ($lifeSteal * .10);
        }

        if ($gameMap->mapType()->isPurgatory()) {
            return $lifeSteal - ($lifeSteal * .20);
        }

        if ($gameMap->mapType()->isTwistedMemories()) {
            return $lifeSteal - ($lifeSteal * .25);
        }

        $hasPurgatoryItem = $this->character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::PURGATORY;
        })->first();

        if (! is_null($hasPurgatoryItem)) {
            if ($gameMap->mapType()->isTheIcePlane()) {
                return $lifeSteal - ($lifeSteal * .20);
            }

            if ($gameMap->mapType()->isDelusionalMemories()) {
                return $lifeSteal - ($lifeSteal * .25);
            }
        }

        return $lifeSteal;
    }

    private function buildBonusDamageForClassBasedOnWeaponTypeEquipped(int $damageStat, string $weaponType, int $amount = 1, ?string $orWeaponType = null): int
    {
        $duelHanded = [
            ItemType::STAVE->value,
            ItemType::HAMMER->value,
            ItemType::BOW->value,
        ];

        $matchingItems = $this->inventory->filter(function ($slot) use ($weaponType, $orWeaponType) {
            return $slot->item->type === $weaponType || ($orWeaponType !== null && $slot->item->type === $orWeaponType);
        });

        $isDualHandedEquipped = $matchingItems->contains(function ($slot) use ($duelHanded) {
            return in_array($slot->item->type, $duelHanded);
        });

        if ($isDualHandedEquipped) {
            $amount = 1;
        }

        $hasEquipped = $amount === 1 ? $matchingItems->isNotEmpty() : $matchingItems->count() >= $amount;

        if ($hasEquipped) {
            return $damageStat * 0.15;
        }

        return $damageStat * 0.05;
    }
}
