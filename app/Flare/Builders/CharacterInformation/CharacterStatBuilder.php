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
use Illuminate\Support\Collection;

class CharacterStatBuilder {

    use FetchEquipped, Boons;

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

    // Debugging:
    private float $startTime = 0;

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

    public function fetchInventory(): Collection {
        if (empty($this->equippedItems)) {
            return collect();
        }

        return $this->equippedItems;
    }

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
     * @return HolyBuilder
     */
    public function holyInfo(): HolyBuilder {
        return $this->holyBuilder;
    }

    /**
     * @return ReductionsBuilder
     */
    public function reductionInfo(): ReductionsBuilder {
        return $this->reductionsBuilder;
    }

    public function canAffixesBeResisted(): bool {
        if (empty($this->equippedItems)) {
            return false;
        }

        return !is_null($this->equippedItems->where('item.effect', ItemEffectsValue::AFFIXES_IRRESISTIBLE)->first());
    }

    public function statMod(string $stat, bool $voided = false): float {
        $baseStat = $this->character->{$stat};

        if (is_null($this->equippedItems)) {
            return $this->applyBoons($baseStat);
        }

        $baseStat = $baseStat + $baseStat * $this->fetchStatFromEquipment($stat, $voided);

        $baseStat = $this->applyBoons($baseStat);
        $baseStat = $this->applyBoons($baseStat, $stat . '_mod');

        if ($this->map->mapType()->isHell() || $this->map->mapType()->isPurgatory()) {
            $baseStat = $baseStat - $baseStat * $this->map->character_attack_reduction;
        }

        return $baseStat;
    }

    public function buildHealth(bool $voided = false): float {
        return $this->statMod('dur', $voided);
    }

    public function buildDefence(bool $voided = false): float {
        $this->defenceBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        return $this->defenceBuilder->buildDefence($voided);
    }

    public function buildDamage(string $type, bool $voided = false): int {

        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            return $stat;
        }

        switch($type) {
            case 'weapon':
                return ceil($this->damageBuilder->buildWeaponDamage($stat, $voided));
            case 'ring':
                return $this->damageBuilder->buildRingDamage();
            case 'spell-damage':
                return ceil($this->damageBuilder->buildSpellDamage($stat, $voided));
            default:
                return 0;
        }
    }

    public function buildTotalAttack(): int {
        $weaponDamage = $this->buildDamage('weapon');
        $ringDamage   = $this->buildDamage('ring');
        $spellDamage  = $this->buildDamage('spell-damage');

        return $weaponDamage + $ringDamage + $spellDamage;
    }

    public function positionalWeaponDamage(string $weaponPosition, bool $voided = false): int {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            return $stat;
        }

        return ceil($this->damageBuilder->buildWeaponDamage($stat, $voided, $weaponPosition));
    }

    public function positionalSpellDamage(string $spellPosition, bool $voided = false): int {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            return $stat;
        }

        return ceil($this->damageBuilder->buildSpellDamage($stat, $voided, $spellPosition));
    }

    public function positionalHealing(string $spellPosition, bool $voided = false): int {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            return $stat;
        }

        return ceil($this->healingBuilder->buildHealing($stat, $voided, $spellPosition));
    }

    public function buildHealing(bool $voided = false): int {
        $stat = $this->statMod($this->character->damage_stat, $voided);

        if (is_null($this->equippedItems)) {
            return $stat;
        }

        return ceil($this->healingBuilder->buildHealing($stat, $voided));
    }

    public function  buildDevouring(string $type): float {

        $itemDevouring = 0;

        if ($this->questItems->isNotEmpty()) {
            $itemDevouring = $this->questItems->sum('item.' . $type);
        }

        if (empty($this->equippedItems)) {
            return $itemDevouring;
        }

        $prefixDevouring  = $this->equippedItems->pluck('item.itemPrefix.' . $type)->toArray();
        $suffixDevouring  = $this->equippedItems->pluck('item.itemSuffix.' . $type)->toArray();

        $bestAffixDevouring = max(array_merge($prefixDevouring, $suffixDevouring));
        $amount             = $itemDevouring + $bestAffixDevouring;

        if ($this->character->map->gameMap->mapType()->isPurgatory()) {
            $amount -= 0.45;
        }

        if ($amount > 1) {
            $amount = 1;
        }

        return $amount;
    }

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
                    $chance = 0.65;
                } else {
                    $chance = 0.45;
                }
            }
        }

        return $chance;
    }

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

    public function buildEntrancingChance($voided): float {

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

    public function getStatReducingPrefix(): ?ItemAffix {

        if (is_null($this->equippedItems)) {
            return null;
        }

        $slot = $this->equippedItems->where('item.itemPrefix.reduces_enemy_stats', '>', 0)->first();

        if (!is_null($slot)) {
            return $slot->item->itemPrefix;
        }

        return null;
    }

    public function getStatReducingSuffixes(): Collection {

        if (is_null($this->equippedItems)) {
            return collect();
        }

        return $this->equippedItems->where('item.itemSuffix.reduces_enemy_stats', '>', 0)->values();
    }


    public function buildAmbush(string $type = 'chance'): float {

        if (is_null($this->equippedItems)) {
            return 0;
        }

        if ($type === 'chance') {
            return $this->equippedItems->where('item.type', 'trinket')->sum('item.ambush_chance');
        }

        return $this->equippedItems->where('item.type', 'trinket')->sum('item.ambush_resistance');
    }

    public function buildCounter(string $type = 'chance'): float {

        if (is_null($this->equippedItems)) {
            return 0;
        }

        if ($type === 'chance') {
            return $this->equippedItems->where('item.type', 'trinket')->sum('item.counter_chance');
        }

        return $this->equippedItems->where('item.type', 'trinket')->sum('item.counter_resistance');
    }

    protected function applyBoons(float $base, ?string $statAttribute = null): float {
        $totalPercent = 0;

        if ($this->characterBoons->isNotEmpty()) {
            if (is_null($statAttribute)) {
                $totalPercent = $this->characterBoons->sum('item.stat_increase');
            } else {
                $totalPercent = $this->characterBoons->sum('item.' . $statAttribute);
            }
        }

        return $base + $base * $totalPercent;
    }

    protected function fetchStatFromEquipment(string $stat, bool $voided = false): float {
        $totalPercentFromEquipped = 0;

        foreach ($this->equippedItems as $slot) {
            $totalPercentFromEquipped += $this->fetchModdedStat($slot->item, $stat, $voided);
        }

        return $totalPercentFromEquipped;
    }

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
