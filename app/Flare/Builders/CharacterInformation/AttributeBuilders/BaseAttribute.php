<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use Illuminate\Support\Collection;

class BaseAttribute {

    protected  Character $character;

    protected ?Collection $inventory;

    protected  Collection $skills;

    public function initialize(Character $character, Collection $skills, ?Collection $inventory): void {

        $this->character = $character;
        $this->inventory = $inventory;
        $this->skills    = $skills;
    }

    protected function getAttributeBonusFromAllItemAffixes(string $attribute): float {
        return $this->inventory->sum('item.itemPrefix.'.$attribute.'_mod') +
            $this->inventory->sum('item.itemSuffix.'.$attribute.'_mod');
    }


    protected function fetchBaseAttributeFromSkills(string $baseAttribute): float {
        $totalPercent = 0;

        foreach ($this->skills as $skill) {
            $totalPercent += ($skill->baseSkill->{$baseAttribute . '_mod_bonus_per_level'} * $skill->level);
        }

        return $totalPercent;
    }

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

    protected function getDamageFromItems(string $type, string $position): int {

        if ($position === 'both') {
            return $this->inventory->where('item.type', $type)->sum('item.base_damage');
        }

        return $this->inventory->where('item.type', $type)->where('position', $position)->sum('item.base_damage');
    }
}
