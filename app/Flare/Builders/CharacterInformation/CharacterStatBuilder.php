<?php

namespace App\Flare\Builders\CharacterInformation;

use App\Flare\Builders\Character\Traits\Boons;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\DamageBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\DefenceBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use Illuminate\Support\Collection;

class CharacterStatBuilder {

    use FetchEquipped, Boons;

    private Character $character;

    private ?Collection $equippedItems;

    private Collection $characterBoons;

    private Collection $skills;

    private GameMap $map;

    private DefenceBuilder $defenceBuilder;

    private DamageBuilder $damageBuilder;

    // Debugging:
    private float $startTime = 0;

    public function __construct(DefenceBuilder $defenceBuilder, DamageBuilder $damageBuilder) {
        $this->defenceBuilder = $defenceBuilder;
        $this->damageBuilder  = $damageBuilder;
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

        $this->characterBoons = $this->fetchCharacterBoons($character);

        $this->map            = $this->character->map->gameMap;

        $this->skills         = $this->character->skills;

        return $this;
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
        $this->damageBuilder->initialize(
            $this->character,
            $this->skills,
            $this->equippedItems,
        );

        switch($type) {
            case 'weapon':
                $stat = $this->statMod($this->character->damage_stat, $voided);

                return ceil($this->damageBuilder->buildWeaponDamage($stat, $voided));
            default:
                return 0;
        }
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
