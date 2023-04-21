<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Quest;
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
                $collections[] = $this->transformNestedPassives($character, $characterPassive);
            }
        }

        return collect($collections);
    }

    public function getPassiveInTraining(Character $character): ?CharacterPassiveSkill {
        return $character->passiveSkills()->whereNotNull('started_at')->first();
    }

    protected function transformNestedPassives(Character $character, CharacterPassiveSkill $passiveSkill) {

        $passiveSkill = $this->assignQuestInfoToPassive($character, $passiveSkill);

        if (is_null($passiveSkill->parent_skill_id)) {
            $passiveSkill->name      = $passiveSkill->passiveSkill->name;
            $passiveSkill->max_level = $passiveSkill->passiveSkill->max_level;
        }

        if ($passiveSkill->children->isNotEmpty()) {
            foreach ($passiveSkill->children as $child) {
                $child            = $this->assignQuestInfoToPassive($character, $child);
                $child->name      = $child->passiveSkill->name;
                $child->max_level = $child->passiveSkill->max_level;

                if ($child->children->isNotEmpty()) {
                    $this->transformNestedPassives($character, $child);
                }
            }
        }

        return $passiveSkill;
    }

    protected function assignQuestInfoToPassive(Character $character, CharacterPassiveSkill $passiveSkill): CharacterPassiveSkill {
        $requiredQuest = $this->getQuest($passiveSkill->passiveSkill->id);

        if (!is_null($requiredQuest)) {
            $passiveSkill->quest_name        = $requiredQuest->name;
            $passiveSkill->is_quest_complete = !is_null($character->questsCompleted()->where('quest_id', $requiredQuest->id)->first());
        } else {
            $passiveSkill->quest_name        = null;
            $passiveSkill->is_quest_complete = false;
        }

        return $passiveSkill;
    }

    protected function getQuest(int $passiveSkillId): ?Quest {
        return Quest::where('unlocks_passive_id', $passiveSkillId)->first();
    }
}
