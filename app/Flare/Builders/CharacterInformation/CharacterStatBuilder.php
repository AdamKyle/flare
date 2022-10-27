<?php

namespace App\Flare\Builders\CharacterInformation;

use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\Builders\Character\Traits\Boons;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\DamageBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\DefenceBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\HealingBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\HolyBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\ReductionsBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemEffectsValue;
use Exception;
use Illuminate\Support\Collection;

class CharacterStatBuilder {

    use FetchEquipped, Boons;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var Collection|null $equippedItems
     */
    private ?Collection $equippedItems;

    /**
     * @var Collection $questItems
     */
    private Collection $questItems;

    /**
     * @var Collection $characterBoons
     */
    private Collection $characterBoons;

    /**
     * @var Collection $skills
     */
    private Collection $skills;

    /**
     * @var GameMap $map
     */
    private GameMap $map;

    /**
     * @var DefenceBuilder $defenceBuilder
     */
    private DefenceBuilder $defenceBuilder;

    /**
     * @var DamageBuilder $damageBuilder
     */
    private DamageBuilder $damageBuilder;

    /**
     * @var HealingBuilder $healingBuilder
     */
    private HealingBuilder $healingBuilder;

    /**
     * @var HolyBuilder $holyBuilder
     */
    private HolyBuilder $holyBuilder;

    /**
     * @var ReductionsBuilder $reductionsBuilder
     */
    private ReductionsBuilder $reductionsBuilder;

    /**
     * @param DefenceBuilder $defenceBuilder
     * @param DamageBuilder $damageBuilder
     * @param HealingBuilder $healingBuilder
     * @param HolyBuilder $holyBuilder
     * @param ReductionsBuilder $reductionsBuilder
     */
    public function __construct(DefenceBuilder $defenceBuilder,
                                DamageBuilder $damageBuilder,
                                HealingBuilder $healingBuilder,
                                HolyBuilder $holyBuilder,
                                ReductionsBuilder $reductionsBuilder
    ) {
        $this->defenceBuilder    = $defenceBuilder;
        $this->damageBuilder     = $damageBuilder;
        $this->healingBuilder    = $healingBuilder;
        $this->holyBuilder       = $holyBuilder;
        $this->reductionsBuilder = $reductionsBuilder;
    }

    /**
     * Set the character and their inventory.
     *
     * @param Character $character
     * @return CharacterStatBuilder
     */
    public function setCharacter(Character $character): CharacterStatBuilder {
        $this->startTime = microtime(true);

        $this->character      = $character;

        $this->equippedItems  = $this->fetchEquipped($character);

        $this->questItems     = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest';
        });

        $this->characterBoons = $this->fetchCharacterBoons($character);

        $this->map            = $this->character->map->gameMap;

        $this->skills         = $this->character->skills;

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

        $this->reductionsBuilder->initialize($this->character, $this->skills, $this->equippedItems);

        return $this;
    }

    /**
     * Fetch inventory.
     *
     * @return Collection
     */
    public function fetchInventory(): Collection {
        if (empty($this->equippedItems)) {
            return collect();
        }

        return $this->equippedItems;
    }

    /**
     * Get class bonus.
     *
     * @return float
     */
    public function classBonus(): float {
        if (empty($this->equippedItems)) {
            return 0.0;
        }

        $suffixClassBonus = $this->equippedItems->sum('item.itemSuffix.class_bonus');
        $prefixClassBonus = $this->equippedItems->sum('item.itemPrefix.class_bonus');

        $total = $suffixClassBonus + $prefixClassBonus;

        if ($total > 1) {
            return 1.0;
        }

        return $total;
    }

    /**
     * Get instance of Holy Builder.
     *
     * @return HolyBuilder
     */
    public function holyInfo(): HolyBuilder {
        return $this->holyBuilder;
    }

    /**
     * Get instance of reduction builder.
     *
     * @return ReductionsBuilder
     */
    public function reductionInfo(): ReductionsBuilder {
        return $this->reductionsBuilder;
    }

    /**
     * Can the characters affixes be resisted?
     *
     * @return bool
     */
    public function canAffixesBeResisted(): bool {
        if ($this->questItems->isEmpty()) {
            return false;
        }

        return !is_null($this->questItems->where('item.effect', ItemEffectsValue::AFFIXES_IRRESISTIBLE)->first());
    }

    /**
     * Get modded stat.
     *
     * @param string $stat
     * @param bool $voided
     * @return float
     */
    public function statMod(string $stat, bool $voided = false): float {
        $baseStat = $this->character->{$stat};

        if (is_null($this->equippedItems)) {
            $baseStat = $this->applyBoons($baseStat);
            return $this->applyBoons($baseStat, $stat . '_mod');
        }

        $baseStat = $baseStat + $baseStat * $this->fetchStatFromEquipment($stat, $voided);

        $baseStat = $this->applyBoons($baseStat);
        $baseStat = $this->applyBoons($baseStat, $stat . '_mod');

        if ($this->map->mapType()->isHell() || $this->map->mapType()->isPurgatory()) {
            $baseStat = $baseStat - $baseStat * $this->map->character_attack_reduction;
        }

        return $baseStat;
    }

    /**
     * Build health based off durability stat.
     *
     * @param bool $voided
     * @return float
     */
    public function buildHealth(bool $voided = false): float {
        return $this->statMod('dur', $voided);
    }

    /**
     * Build Defence.
     *
     * @param bool $voided
     * @return float
     */
    public function buildDefence(bool $voided = false): float {
        $this->defenceBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        $defence   = $this->defenceBuilder->buildDefence($voided);
        $holyBonus = $this->holyInfo()->fetchDefenceBonus();

        return $defence + $defence * $holyBonus;
    }

    /**
     * Build damage.
     *
     * @param string $type
     * @param bool $voided
     * @return int
     * @throws Exception
     */
    public function buildDamage(string $type, bool $voided = false): int {

        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {

            if ($type === 'weapon') {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            if ($type === 'spell-damage' && $this->character->classType()->isCaster()) {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            return 0;
        }

        switch($type) {
            case 'weapon':
                $damage = $this->damageBuilder->buildWeaponDamage($stat, $voided);
                break;
            case 'ring':
                return $this->damageBuilder->buildRingDamage();
            case 'spell-damage':
                $damage = $this->damageBuilder->buildSpellDamage($stat, $voided);
                break;
            default:
                $damage = 0;
        }

        return ceil($damage + $damage * $this->holyInfo()->fetchAttackBonus());
    }

    /**
     * Build total attacks
     *
     * Includes: Weapons, Rings and Spell Damage.
     *
     * @return int
     * @throws Exception
     */
    public function buildTotalAttack(): int {
        $weaponDamage = $this->buildDamage('weapon');
        $ringDamage   = $this->buildDamage('ring');
        $spellDamage  = $this->buildDamage('spell-damage');

        return $weaponDamage + $ringDamage + $spellDamage;
    }

    /**
     * Build Positional Weapon Damage.
     *
     * @param string $weaponPosition
     * @param bool $voided
     * @return int
     */
    public function positionalWeaponDamage(string $weaponPosition, bool $voided = false): int {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            $value = $stat / 2;

            return $value < 5 ? 5 : $value;
        }

        $damage = $this->damageBuilder->buildWeaponDamage($stat, $voided, $weaponPosition);

        return ceil($damage + $damage * $this->holyInfo()->fetchAttackBonus());
    }

    /**
     * Build Positional Spell Damage.
     *
     * @param string $spellPosition
     * @param bool $voided
     * @return int
     * @throws Exception
     */
    public function positionalSpellDamage(string $spellPosition, bool $voided = false): int {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            if ($this->character->classType()->isCaster()) {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            return 0;
        }

        $damage = $this->damageBuilder->buildSpellDamage($stat, $voided, $spellPosition);

        return ceil($damage + $damage * $this->holyInfo()->fetchAttackBonus());
    }

    /**
     * Build positional healing.
     *
     * @param string $spellPosition
     * @param bool $voided
     * @return int
     * @throws Exception
     */
    public function positionalHealing(string $spellPosition, bool $voided = false): int {

        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {

            if ($this->character->classType()->isProphet()) {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            return 0;
        }

        $healing = $this->healingBuilder->buildHealing($stat, $voided, $spellPosition);

        return ceil($healing + $healing * $this->holyInfo()->fetchHealingBonus());
    }

    /**
     * Build total healing.
     *
     * @param bool $voided
     * @return int
     * @throws Exception
     */
    public function buildHealing(bool $voided = false): int {

        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            if ($this->character->classType()->isProphet()) {
                $value = $stat / 2;

                return $value < 5 ? 5 : $value;
            }

            return 0;
        }

        $healing = $this->healingBuilder->buildHealing($stat, $voided);

        return ceil($healing + $healing * $this->holyInfo()->fetchHealingBonus());
    }

    /**
     * Build Devouring info for type.
     *
     * type can be Devouring Darkness or Devouring Light
     *
     * @param string $type
     * @return float
     */
    public function  buildDevouring(string $type): float {

        $itemDevouring = 0;

        if ($this->questItems->isNotEmpty()) {
            $itemDevouring = $this->questItems->sum('item.' . $type);
        }

        if (empty($this->equippedItems)) {
            if ($this->character->map->gameMap->mapType()->isPurgatory()) {
                if ($itemDevouring >= 0.45) {
                    $itemDevouring -= 0.45;
                }
            }

            return $itemDevouring;
        }

        $prefixDevouring  = $this->equippedItems->pluck('item.itemPrefix.' . $type)->toArray();
        $suffixDevouring  = $this->equippedItems->pluck('item.itemSuffix.' . $type)->toArray();

        $bestAffixDevouring = max(array_merge($prefixDevouring, $suffixDevouring));
        $amount             = $itemDevouring + $bestAffixDevouring;

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
     * @return float
     * @throws Exception
     */
    public function buildResurrectionChance(): float {
        if (empty($this->equippedItems)) {
            return  0;
        }

        $chance = $this->equippedItems->where('item.type', '=', 'spell-healing')->sum('item.resurrection_chance');

        if ($this->character->classType()->isProphet()) {
            $chance += 0.05;
        }

        if ($chance > 0) {
            if ($this->character->map->gameMap->mapType()->isPurgatory() && $chance > 0.45) {
                if ($this->character->classType()->isProphet()) {
                    $chance = min($chance, 0.65);
                } else {
                    $chance = min($chance, 0.45);
                }
            }
        }

        return $chance;
    }

    /**
     * Build affix damage based on type.
     *
     * @param string $type
     * @param bool $voided
     * @return float|int
     */
    public function buildAffixDamage(string $type, bool $voided = false): float|int {
        switch($type) {
            case 'affix-stacking-damage':
                return $this->damageBuilder->buildAffixStackingDamage($voided);
            case 'affix-non-stacking':
                return $this->damageBuilder->buildAffixNonStackingDamage($voided);
            case 'affix-irresistible-damage-stacking':
                return $this->damageBuilder->buildIrresistibleStackingAffixDamage($voided);
            case 'affix-irresistible-damage-non-stacking':
                return $this->damageBuilder->buildIrresistibleNonStackingAffixDamage($voided);
            case 'life-stealing':
                return $this->damageBuilder->buildLifeStealingDamage($voided);
            default:
                return 0;
        }
    }

    /**
     * Build entrancing chance.
     *
     * @param bool $voided
     * @return float
     */
    public function buildEntrancingChance(bool $voided = false): float {

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
     * Get stat reducing prefix.
     *
     * Takes the Highest one.
     *
     * @return ItemAffix|null
     */
    public function getStatReducingPrefix(): ?ItemAffix {

        if (is_null($this->equippedItems)) {
            return null;
        }

        foreach ($this->equippedItems as $slot) {
            if (!is_null($slot->item->itemPrefix)) {
                if ($slot->item->itemPrefix->reduces_enemy_stats) {
                    return $slot->item->itemPrefix;
                }
            }
        }

        return null;
    }

    /**
     * Get all stat reducing suffixes
     *
     * @return array
     */
    public function getStatReducingSuffixes(): array {

        if (is_null($this->equippedItems)) {
            return [];
        }

        $suffixes = [];

        foreach ($this->equippedItems as $slot) {
            if (!is_null($slot->item->itemSuffix)) {
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
     *
     * @param string $type
     * @return float
     */
    public function buildAmbush(string $type = 'chance'): float {

        if (is_null($this->equippedItems)) {
            return 0;
        }

        if ($type === 'chance') {
            return $this->equippedItems->where('item.type', 'trinket')->sum('item.ambush_chance');
        }

        return $this->equippedItems->where('item.type', 'trinket')->sum('item.ambush_resistance');
    }

    /**
     * Build counter based off trinkets.
     *
     * - Builds chance or resistance
     *
     * @param string $type
     * @return float
     */
    public function buildCounter(string $type = 'chance'): float {

        if (is_null($this->equippedItems)) {
            return 0;
        }

        if ($type === 'chance') {
            return $this->equippedItems->where('item.type', 'trinket')->sum('item.counter_chance');
        }

        return $this->equippedItems->where('item.type', 'trinket')->sum('item.counter_resistance');
    }

    /**
     * Apply boons.
     *
     * @param float $base
     * @param string|null $statAttribute
     * @return float
     */
    protected function applyBoons(float $base, ?string $statAttribute = null): float {
        $totalPercent = 0;

        if ($this->characterBoons->isNotEmpty()) {
            if (is_null($statAttribute)) {
                $totalPercent = $this->characterBoons->sum('itemUsed.stat_increase');
            } else {
                $totalPercent = $this->characterBoons->sum('itemUsed.' . $statAttribute);
            }
        }

        return $base + $base * $totalPercent;
    }

    /**
     * Fetch stat from equipment.
     *
     * @param string $stat
     * @param bool $voided
     * @return float
     */
    protected function fetchStatFromEquipment(string $stat, bool $voided = false): float {
        $totalPercentFromEquipped = 0;

        foreach ($this->equippedItems as $slot) {
            $totalPercentFromEquipped += $this->fetchModdedStat($slot->item, $stat, $voided);
        }

        return $totalPercentFromEquipped;
    }

    /**
     * Fetch modded stat
     *
     * @param Item $item
     * @param string $stat
     * @param bool $voided
     * @return float
     */
    private function fetchModdedStat(Item $item, string $stat, bool $voided = false): float {
        $staMod          = $item->{$stat . '_mod'};
        $totalPercentage = !is_null($staMod) ? $staMod : 0.0;

        if ($voided) {
            return $totalPercentage;
        }

        $itemPrefix = $item->itemPrefix;
        $itemSuffix = $item->itemSuffix;

        if (!is_null($itemPrefix)) {
            $prefixMod        = $itemPrefix->{$stat . '_mod'};
            $totalPercentage += !is_null($prefixMod) ? $prefixMod : 0.0;
        }

        if (!is_null($itemSuffix)) {
            $suffixMod        = $itemSuffix->{$stat . '_mod'};
            $totalPercentage += !is_null($suffixMod) ? $suffixMod : 0.0;
        }

        $totalPercentage += $item->holy_stack_stat_bonus;

        return $totalPercentage;
    }
}
