<?php

namespace App\Game\Character\Builders\InformationBuilders;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Traits\ElementAttackData;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Character\Concerns\Boons;
use App\Game\Character\Concerns\FetchEquipped;
use Exception;
use Facades\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Support\Collection;

class CharacterStatBuilder
{
    use Boons, ElementAttackData, FetchEquipped;

    private Character $character;

    private ?Collection $equippedItems;

    private Collection $questItems;

    private Collection $characterBoons;

    private Collection $skills;

    private GameMap $map;

    private DefenceBuilder $defenceBuilder;

    private DamageBuilder $damageBuilder;

    private HealingBuilder $healingBuilder;

    private HolyBuilder $holyBuilder;

    private ReductionsBuilder $reductionsBuilder;

    private ElementalAtonement $elementalAtonement;

    private bool $ignoreReductions = false;

    public function __construct(
        DefenceBuilder $defenceBuilder,
        DamageBuilder $damageBuilder,
        HealingBuilder $healingBuilder,
        HolyBuilder $holyBuilder,
        ReductionsBuilder $reductionsBuilder,
        ElementalAtonement $elementalAtonement
    ) {
        $this->defenceBuilder = $defenceBuilder;
        $this->damageBuilder = $damageBuilder;
        $this->healingBuilder = $healingBuilder;
        $this->holyBuilder = $holyBuilder;
        $this->reductionsBuilder = $reductionsBuilder;
        $this->elementalAtonement = $elementalAtonement;
    }

    /**
     * Set the character and their inventory.
     */
    public function setCharacter(Character $character, bool $ignoreReductions = false): CharacterStatBuilder
    {
        $this->ignoreReductions = $ignoreReductions;

        $this->character = $character;

        $this->equippedItems = $this->fetchEquipped($character);

        $this->questItems = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'quest';
        });

        $this->characterBoons = $this->fetchCharacterBoons($character);

        $this->map = $this->character->map->gameMap;

        $this->skills = $this->character->skills;

        $this->damageBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        $this->healingBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        $this->holyBuilder->initialize($this->character, $this->skills, $this->equippedItems);

        $this->elementalAtonement->initialize($this->character, $this->skills, $this->equippedItems);

        $this->reductionsBuilder->initialize($this->character, $this->skills, $this->equippedItems);

        return $this;
    }

    /**
     * Return the character.
     */
    public function character(): Character
    {
        return $this->character;
    }

    /**
     * Fetch inventory.
     */
    public function fetchInventory(): Collection
    {
        if (empty($this->equippedItems)) {
            return collect();
        }

        return $this->equippedItems;
    }

    /**
     * Get class bonus.
     *
     * - Default bonus is 5%.
     */
    public function classBonus(): float
    {
        $classBonusSkill = $this->character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->whereNotNull('game_class_id');
            })
            ->first();

        if (is_null($classBonusSkill)) {
            return 0;
        }

        $classBonus = $classBonusSkill->baseSkill->class_bonus * $classBonusSkill->level;

        return min(1, $classBonus);
    }

    /**
     * Get instance of Holy Builder.
     */
    public function holyInfo(): HolyBuilder
    {
        return $this->holyBuilder;
    }

    /**
     * Get instance of reduction builder.
     */
    public function reductionInfo(): ReductionsBuilder
    {
        return $this->reductionsBuilder;
    }

    /**
     * Can the characters affixes be resisted?
     */
    public function canAffixesBeResisted(): bool
    {
        if ($this->questItems->isEmpty()) {
            return false;
        }

        return ! is_null($this->questItems->where('item.effect', ItemEffectsValue::AFFIXES_IRRESISTIBLE)->first());
    }

    /**
     * Get modded stat.
     */
    public function statMod(string $stat, bool $voided = false): float
    {
        $baseStat = $this->character->{$stat};

        $baseStat = $baseStat + $baseStat * $this->fetchStatFromEquipment($stat, $voided);

        $baseStat = $this->applyBoons($baseStat);
        $baseStat = $this->applyBoons($baseStat, $stat.'_mod');
        $baseStat += ItemSkillAttribute::fetchModifier($this->character, $stat.'_mod');

        if ($stat === $this->character->damage_stat) {
            $classSpecialsBonus = $this->character->classSpecialsEquipped
                ->where('equipped', true)
                ->where('base_damage_stat_increase', '>', 0)
                ->sum('base_damage_stat_increase');

            $baseStat = $baseStat + $baseStat * ($classSpecialsBonus + $this->character->base_damage_stat_mod);
        } else {
            $baseStat = $baseStat + $baseStat * $this->character->base_stat_mod;
        }

        $reduction = $this->getMapCharacterReductions();

        return $baseStat - $baseStat * $reduction;
    }

    /**
     * Get map reductions for characters.
     */
    protected function getMapCharacterReductions(): float
    {
        if ($this->ignoreReductions) {
            return 0;
        }

        if ($this->map->mapType()->isHell() ||
            $this->map->mapType()->isPurgatory() ||
            $this->map->mapType()->isTwistedMemories()
        ) {
            return $this->map->character_attack_reduction;
        }

        $purgatoryQuestItem = $this->character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::PURGATORY;
        })->first();

        if (! is_null($purgatoryQuestItem)) {

            if ($this->map->mapType()->isTheIcePlane() || $this->map->mapType()->isDelusionalMemories()) {
                return $this->map->character_attack_reduction;
            }
        }

        return 0;
    }

    /**
     * Build health based off durability stat.
     */
    public function buildHealth(bool $voided = false): float
    {
        $classSpecialsBonus = $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->where('health_mod', '>', 0)
            ->sum('health_mod');

        $health = $this->statMod('dur', $voided);

        return $health + ($health * $classSpecialsBonus);
    }

    /**
     * Build the characters over all elemental atonement.
     */
    public function buildElementalAtonement(): ?array
    {
        return $this->elementalAtonement->calculateAtonement();
    }

    public function getDefenceBuilder(): DefenceBuilder
    {
        $this->defenceBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        return $this->defenceBuilder;
    }

    public function getDamageBuilder(): DamageBuilder
    {
        $this->damageBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        return $this->damageBuilder;
    }

    public function getHealingBuilder(): HealingBuilder
    {
        $this->healingBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        return $this->healingBuilder;
    }

    /**
     * Build Defence.
     */
    public function buildDefence(bool $voided = false): float
    {
        $this->defenceBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        $defence = $this->defenceBuilder->buildDefence($this->classBonus(), $voided);
        $holyBonus = $this->holyInfo()->fetchDefenceBonus();
        $defence = $this->applyBoons($defence, 'base_ac_mod');

        $classSpecialsBonus = $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->where('base_ac_mod', '>', 0)
            ->sum('base_ac_mod');

        $itemSkillBonus = 0;

        if (! is_null($this->equippedItems)) {
            $itemSkillBonus = ItemSkillAttribute::fetchModifier($this->character, 'base_ac');
        }

        return $defence + ($defence * ($holyBonus + $classSpecialsBonus + $itemSkillBonus));
    }

    /**
     * Build time out modifier bonus for type.
     */
    public function buildTimeOutModifier(string $type): float
    {
        return $this->damageBuilder->fetchBaseAttributeFromSkills($type);
    }

    /**
     * Build damage.
     *
     * @throws Exception
     */
    public function buildDamage(string|array $type, bool $voided = false): int
    {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        $validWeaponTypes = array_diff(
            array_column(ItemType::cases(), 'value'),
            [ItemType::RING->value, ItemType::SPELL_DAMAGE->value, ItemType::SPELL_HEALING->value]
        );

        $types = is_array($type) ? $type : [$type];

        if (is_null($this->equippedItems)) {
            if (!empty(array_intersect($types, $validWeaponTypes))) {
                if ($this->character->classType()->isAlcoholic()) {
                    return $stat + ($stat * 0.25);
                }

                if ($this->character->classType()->isFighter()) {
                    return $stat + ($stat * 0.05);
                }

                $value = $stat * 0.02;
                return max($value, 5);
            }

            if (in_array(ItemType::SPELL_DAMAGE->value, $types) && $this->character->classType()->isHeretic()) {
                $value = $stat * 0.15;
                return max($value, 5);
            }

            return 0;
        }

        $ringDamage = $this->getRingDamage($types);
        $spellDamage = $this->getSpellDamage($types, $voided);
        $weaponDamage = $this->getWeaponDamage($stat, $types, $validWeaponTypes, $voided);

        $damage = $ringDamage + $spellDamage + $weaponDamage;

        $classSpecialsBonus = $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->where('base_damage_mod', '>', 0)
            ->sum('base_damage_mod');

        $itemSkillBonus = 0;

        if (!is_null($this->equippedItems)) {
            $itemSkillBonus = ItemSkillAttribute::fetchModifier($this->character, 'base_damage');
        }

        return ceil($damage + ($damage * ($this->holyInfo()->fetchAttackBonus() + $classSpecialsBonus + $itemSkillBonus)));
    }

    private function getRingDamage(array $types): int {
        if (in_array(ItemType::RING->value, $types)) {
            return $this->damageBuilder->buildRingDamage();
        }

        return 0;
    }

    private function getSpellDamage(array $types, bool $voided = false): int {
        if (in_array(ItemType::SPELL_DAMAGE->value, $types)) {
            return $this->spellDamageBonus(
                $this->damageBuilder->buildSpellDamage($voided),
                $voided
            );
        }

        return 0;
    }

    private function getWeaponDamage(string $stat, array $types, array $validWeaponTypes, bool $voided = false): int {
        if (!empty(array_intersect($types, $validWeaponTypes))) {
            return $this->damageBuilder->buildWeaponDamage($stat, $voided);
        }

        return 0;
    }


    /**
     * Add bonus to spell damage.
     *
     * - Class should be heretic or arcane alchemist.
     * - Adds 30% of their int to the damage.
     *
     * @throws Exception
     */
    protected function spellDamageBonus(int $damage, bool $voided = false): int
    {
        if ($this->character->class->type()->isHeretic() || $this->character->class->type()->isArcaneAlchemist()) {
            $intMod = $this->statMod('int', $voided) * 0.30;

            return ceil($intMod + $damage);
        }

        if ($this->character->classType()->isAlcoholic()) {
            return floor($damage - ($damage * 0.50));
        }

        return $damage;
    }

    /**
     * Healing Bonus.
     *
     * - Prophets get 30% of their CHR
     * - Arcane Alchemists get 10% of their CHR
     */
    protected function healingBonus(int $healing, bool $voided = false): int
    {
        if ($this->character->class->type()->isProphet()) {
            $chrMod = $this->statMod('chr', $voided) * 0.30;

            return ceil($chrMod + $healing);
        }

        if ($this->character->class->type()->isCleric()) {
            $chrMod = $this->statMod('chr', $voided) * 0.45;

            return ceil($chrMod + $healing);
        }

        if ($this->character->class->type()->isArcaneAlchemist()) {
            $chrMod = $this->statMod('chr', $voided) * 0.10;

            return ceil($chrMod + $healing);
        }

        if ($this->character->class->type()->isRanger()) {
            $chrMod = $this->statMod('chr', $voided) * 0.15;

            return ceil($chrMod + $healing);
        }

        return $healing;
    }

    /**
     * Build total attacks
     *
     * Includes: Weapons, Rings and Spell Damage.
     *
     * @throws Exception
     */
    public function buildTotalAttack(): int
    {
        $weaponDamage = $this->buildDamage('weapon') + $this->buildDamage('weapon');
        $ringDamage = $this->buildDamage('ring') + $this->buildDamage('ring');
        $spellDamage = $this->buildDamage('spell-damage') + $this->buildDamage('spell-damage');

        return $weaponDamage + $ringDamage + $spellDamage;
    }

    /**
     * Build Positional Weapon Damage.
     *
     * @throws Exception
     */
    public function positionalWeaponDamage(string $weaponPosition, bool $voided = false): int
    {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            $value = $stat / 2;

            return $value < 5 ? 5 : $value;
        }

        $damage = $this->damageBuilder->buildWeaponDamage($stat, $voided, $weaponPosition);

        $classSpecialsBonus = $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->where('base_damage_mod', '>', 0)
            ->sum('base_damage_mod');

        return ceil($damage + ($damage * ($this->holyInfo()->fetchAttackBonus() + $classSpecialsBonus)));
    }

    /**
     * Build Positional Spell Damage.
     *
     * @throws Exception
     */
    public function positionalSpellDamage(string $spellPosition, bool $voided = false): int
    {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            if ($this->character->classType()->isHeretic() || $this->character->classtype()->isArcaneAlchemist()) {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            return 0;
        }

        $damage = $this->spellDamageBonus($this->damageBuilder->buildSpellDamage($voided, $spellPosition), $voided);

        $classSpecialsBonus = $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->where('base_damage_mod', '>', 0)
            ->sum('base_damage_mod');

        return ceil($damage + ($damage * ($this->holyInfo()->fetchAttackBonus() + $classSpecialsBonus)));
    }

    /**
     * Build positional healing.
     *
     * @throws Exception
     */
    public function positionalHealing(string $spellPosition, bool $voided = false): int
    {

        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            if ($this->character->classType()->isProphet()) {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            return 0;
        }

        $healing = $this->healingBonus($this->healingBuilder->buildHealing($voided, $spellPosition), $voided);

        $classSpecialsBonus = $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->where('base_healing_mod', '>', 0)
            ->sum('base_healing_mod');

        return ceil($healing + ($healing * ($this->holyInfo()->fetchHealingBonus() + $classSpecialsBonus)));
    }

    /**
     * Build total healing.
     *
     * @throws Exception
     */
    public function buildHealing(bool $voided = false): int
    {

        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            if ($this->character->classType()->isProphet() || $this->character->classType()->isRanger()) {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            return 0;
        }

        $healing = $this->healingBonus($this->healingBuilder->buildHealing($voided), $voided);

        $classSpecialsBonus = $this->character->classSpecialsEquipped
            ->where('equipped', true)
            ->where('base_healing_mod', '>', 0)
            ->sum('base_healing_mod');

        $itemSkillBonus = 0;

        if (! is_null($this->equippedItems)) {
            $itemSkillBonus = ItemSkillAttribute::fetchModifier($this->character, 'base_healing');
        }

        return ceil($healing + ($healing * ($this->holyInfo()->fetchHealingBonus() + $classSpecialsBonus + $itemSkillBonus)));
    }

    /**
     * Build Devouring info for type.
     *
     * type can be Devouring Darkness or Devouring Light
     */
    public function buildDevouring(string $type): float
    {

        $itemDevouring = 0;

        if ($this->questItems->isNotEmpty()) {
            $itemDevouring = $this->questItems->sum('item.'.$type);
        }

        if (empty($this->equippedItems)) {
            if ($this->character->map->gameMap->mapType()->isPurgatory()) {
                if ($itemDevouring >= 0.45) {
                    $itemDevouring -= 0.45;
                }
            }

            return $itemDevouring;
        }

        $prefixDevouring = $this->equippedItems->pluck('item.itemPrefix.'.$type)->toArray();
        $suffixDevouring = $this->equippedItems->pluck('item.itemSuffix.'.$type)->toArray();

        $bestAffixDevouring = max(array_merge($prefixDevouring, $suffixDevouring));
        $amount = $itemDevouring + $bestAffixDevouring;

        if ($this->character->map->gameMap->mapType()->isPurgatory()) {
            if ($amount >= 0.45) {
                $amount -= 0.45;
            }
        }

        if ($amount > 1) {
            $amount = 1;
        }

        return floatval(number_format($amount, 2, '.', ''));
    }

    /**
     * Build resurrection chance.
     *
     * @throws Exception
     */
    public function buildResurrectionChance(): float
    {
        if (empty($this->equippedItems)) {
            return 0;
        }

        $chance = $this->equippedItems->where('item.type', '=', 'spell-healing')->sum('item.resurrection_chance');

        // Add bonus for Prophet or Cleric
        if ($this->character->classType()->isProphet() || $this->character->classType()->isCleric()) {
            $chance += 0.05;
        }

        // Handle map type restrictions
        if ($chance > 0) {
            if (($this->character->map->gameMap->mapType()->isPurgatory() || $this->character->map->gameMap->mapType()->isTwistedMemories()) && $chance > 0.45) {
                if ($this->character->classType()->isProphet()) {
                    return min($chance, 0.65);
                } else {
                    return min($chance, 0.45);
                }
            }
        }

        // Cap chance for Vampires at 0.95
        if ($this->character->classType()->isVampire() && $chance > 0.95) {
            return 0.95;
        }

        // Cap chance for non-Prophet and non-Cleric classes at 0.75
        if (! $this->character->classType()->isProphet() && ! $this->character->classType()->isCleric() && $chance > 0.75) {
            return 0.75;
        }

        // Cap chance at 1.0
        if ($chance > 1.0) {
            $chance = 1.0;
        }

        return $chance;
    }

    /**
     * Build affix damage based on type.
     */
    public function buildAffixDamage(string $type, bool $voided = false): float|int
    {
        switch ($type) {
            case 'affix-stacking-damage':
                return $this->damageBuilder->buildAffixStackingDamage($voided);
            case 'affix-non-stacking':
                return $this->damageBuilder->buildAffixNonStackingDamage($voided);
            case 'life-stealing':
                return $this->damageBuilder->buildLifeStealingDamage($voided);
            default:
                return 0;
        }
    }

    /**
     * Build entrancing chance.
     */
    public function buildEntrancingChance(bool $voided = false): float
    {

        if ($voided || is_null($this->equippedItems)) {
            return 0;
        }

        $entrancingAmountSuffix = $this->equippedItems->sum('item.itemSuffix.entranced_chance');
        $entrancingAmountPrefix = $this->equippedItems->sum('item.itemPrefix.entranced_chance');

        $entranceAmount = $entrancingAmountPrefix + $entrancingAmountSuffix;

        if ($entranceAmount > 1) {
            $entranceAmount = 1.0;
        }

        return $entranceAmount;
    }

    /**
     * Build Resistance Chance
     */
    public function buildResistanceReductionChance(bool $voided = false): float
    {
        if ($voided || is_null($this->equippedItems)) {
            return 0;
        }

        $resistanceReduction = $this->equippedItems->sum('item.itemPrefix.resistance_reduction');

        if ($resistanceReduction > 1) {
            $resistanceReduction = 1.0;
        }

        return $resistanceReduction;
    }

    /**
     * Get stat reducing prefix.
     *
     * Takes the Highest one.
     */
    public function getStatReducingPrefix(): ?ItemAffix
    {

        if (is_null($this->equippedItems)) {
            return null;
        }

        foreach ($this->equippedItems as $slot) {
            if (! is_null($slot->item->itemPrefix)) {
                if ($slot->item->itemPrefix->reduces_enemy_stats) {
                    return $slot->item->itemPrefix;
                }
            }
        }

        return null;
    }

    /**
     * Get all stat reducing suffixes
     */
    public function getStatReducingSuffixes(): array
    {

        if (is_null($this->equippedItems)) {
            return [];
        }

        $suffixes = [];

        foreach ($this->equippedItems as $slot) {
            if (! is_null($slot->item->itemSuffix)) {
                if ($slot->item->itemSuffix->reduces_enemy_stats) {
                    $suffixes[] = $slot->item->itemSuffix;
                }
            }
        }

        return $suffixes;
    }

    /**
     * Build ambush based off trinkets.
     *
     * - Builds chance or resistance
     */
    public function buildAmbush(string $type = 'chance'): float
    {

        if (is_null($this->equippedItems)) {
            return 0;
        }

        if ($type === 'chance') {
            $chance = $this->equippedItems->where('item.type', 'trinket')->sum('item.ambush_chance');

            return min($chance, 0.95);
        }

        $chance = $this->equippedItems->where('item.type', 'trinket')->sum('item.ambush_resistance');

        return min($chance, 0.95);
    }

    /**
     * Build counter based off trinkets.
     *
     * - Builds chance or resistance
     */
    public function buildCounter(string $type = 'chance'): float
    {

        if (is_null($this->equippedItems)) {
            return 0;
        }

        if ($type === 'chance') {
            $chance = $this->equippedItems->where('item.type', 'trinket')->sum('item.counter_chance');

            return min($chance, 0.95);
        }

        $chance = $this->equippedItems->where('item.type', 'trinket')->sum('item.counter_resistance');

        return min($chance, 0.95);
    }

    /**
     * Apply boons.
     */
    protected function applyBoons(float $base, ?string $statAttribute = null): float
    {
        $totalPercent = 0;

        if ($this->characterBoons->isNotEmpty()) {
            if (is_null($statAttribute)) {
                $totalPercent = $this->characterBoons->sum('itemUsed.increase_stat_by');
            } else {
                $totalPercent = $this->characterBoons->sum('itemUsed.'.$statAttribute);
            }
        }

        return $base + $base * $totalPercent;
    }

    /**
     * Fetch stat from equipment.
     */
    protected function fetchStatFromEquipment(string $stat, bool $voided = false): float
    {
        $totalPercentFromEquipped = 0;

        if (is_null($this->equippedItems)) {
            return $totalPercentFromEquipped;
        }

        foreach ($this->equippedItems as $slot) {
            $totalPercentFromEquipped += $this->fetchModdedStat($slot->item, $stat, $voided);
        }

        return $totalPercentFromEquipped;
    }

    /**
     * Fetch modded stat
     */
    private function fetchModdedStat(Item $item, string $stat, bool $voided = false): float
    {
        $staMod = $item->{$stat.'_mod'};
        $totalPercentage = ! is_null($staMod) ? $staMod : 0.0;

        if ($voided) {
            return $totalPercentage;
        }

        $itemPrefix = $item->itemPrefix;
        $itemSuffix = $item->itemSuffix;

        if (! is_null($itemPrefix)) {
            $prefixMod = $itemPrefix->{$stat.'_mod'};
            $totalPercentage += ! is_null($prefixMod) ? $prefixMod : 0.0;
        }

        if (! is_null($itemSuffix)) {
            $suffixMod = $itemSuffix->{$stat.'_mod'};
            $totalPercentage += ! is_null($suffixMod) ? $suffixMod : 0.0;
        }

        $totalPercentage += $item->holy_stack_stat_bonus;

        return $totalPercentage;
    }
}
