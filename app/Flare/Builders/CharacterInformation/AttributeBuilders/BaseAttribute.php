<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Values\WeaponTypes;
use Illuminate\Support\Collection;

class BaseAttribute {

    /**
     * @var Character $character
     */
    protected  Character $character;

    /**
     * @var Collection|null $inventory
     */
    protected ?Collection $inventory;

    /**
     * @var Collection $skills
     */
    protected  Collection $skills;

    /**
     * @param Character $character
     * @param Collection $skills
     * @param Collection|null $inventory
     * @return void
     */
    public function initialize(Character $character, Collection $skills, ?Collection $inventory): void {

        $this->character = $character;
        $this->inventory = $inventory;
        $this->skills    = $skills;
    }

    /**
     * Get attribute bonus from all item affixes.
     *
     * @param string $attribute
     * @return float
     */
    protected function getAttributeBonusFromAllItemAffixes(string $attribute): float {
        return $this->inventory->sum('item.itemPrefix.'.$attribute.'_mod') +
            $this->inventory->sum('item.itemSuffix.'.$attribute.'_mod');
    }

    /**
     * Fetch attribute bonus from skills.
     *
     * @param string $baseAttribute
     * @return float
     */
    public function fetchBaseAttributeFromSkills(string $baseAttribute): float {
        $totalPercent = 0;

        foreach ($this->skills as $skill) {
            $totalPercent += ($skill->baseSkill->{$baseAttribute . '_mod_bonus_per_level'} * $skill->level);
        }

        return $totalPercent;
    }

    /**
     * Should we include skill damage?
     *
     * @param GameClass $class
     * @param string $type
     * @return bool
     */
    protected function shouldIncludeSkillDamage(GameClass $class, string $type): bool {
        switch($type) {
            case 'weapon':
                return $class->type()->isNonCaster();
            case 'spell':
                return $class->type()->isCaster();
            case 'healing':
                return $class->type()->isHealer();
            default:
                false;
        }
    }

    /**
     * Get damage from items.
     *
     * @param string $position
     * @return int
     */
    protected function getDamageFromWeapons(string $position): int {

        if ($position === 'both') {
            return $this->inventory->whereIn('item.type', [
                WeaponTypes::WEAPON,
                WeaponTypes::HAMMER,
                WeaponTypes::BOW,
                WeaponTypes::STAVE,
                WeaponTypes::GUN,
                WeaponTypes::FAN,
                WeaponTypes::MACE
            ])->sum('item.base_damage');
        }

        return $this->inventory->whereIn('item.type', [
            WeaponTypes::WEAPON,
            WeaponTypes::HAMMER,
            WeaponTypes::BOW,
            WeaponTypes::STAVE,
            WeaponTypes::GUN,
            WeaponTypes::FAN,
            WeaponTypes::MACE
        ])->where('position', $position)
          ->sum('item.base_damage');
    }

    protected function getDamageFromItems(string $type, string $position): int {
        if ($position === 'both') {
            return $this->inventory->whereIn('item.type', $type)->sum('item.base_damage');
        }

        return $this->inventory->where('item.type', $type)
                               ->where('position', $position)
                               ->sum('item.base_damage');
    }

    /**
     * Get healing from items.
     *
     * @param string $type
     * @param string $position
     * @return int
     */
    protected function getHealingFromItems(string $type, string $position): int {

        if ($position === 'both') {
            return $this->inventory->where('item.type', $type)->sum('item.base_healing');
        }

        return $this->inventory->where('item.type', $type)->where('position', $position)->sum('item.base_healing');
    }
}
