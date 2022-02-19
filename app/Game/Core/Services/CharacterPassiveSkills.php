<?php

namespace App\Game\Core\Services;

use Illuminate\Support\Collection;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\PassiveSkill;

class CharacterPassiveSkills {

    public function getPassiveSkills(Character $character): Collection {
        $passiveSkills = PassiveSkill::where('is_parent', true)->get();

        $collections = [];

        foreach ($passiveSkills as $passiveSkill) {
            $characterPassive = $character->passiveSkills()->where('passive_skill_id', $passiveSkill->id)->with('children')->first();

            if (!is_null($characterPassive)) {
                $collections[] = $this->transformNestedPassives($characterPassive);
            }
        }

        return collect($collections);
    }

    protected function transformNestedPassives(CharacterPassiveSkill $passiveSkill) {

        if (is_null($passiveSkill->parent_skill_id)) {
            $passiveSkill->name      = $passiveSkill->passiveSkill->name;
            $passiveSkill->max_level = $passiveSkill->passiveSkill->max_level;
        }

        if ($passiveSkill->children->isNotEmpty()) {
            foreach ($passiveSkill->children as $child) {
                $child->name      = $child->passiveSkill->name;
                $child->max_level = $child->passiveSkill->max_level;

                if ($child->children->isNotEmpty()) {
                    $this->transformNestedPassives($child);
                }
            }
        }

        return $passiveSkill;
    }
}
