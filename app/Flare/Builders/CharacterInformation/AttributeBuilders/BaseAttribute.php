<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

use App\Flare\Models\Character;
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
}
