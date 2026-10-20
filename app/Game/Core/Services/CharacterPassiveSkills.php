<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class CharacterPassiveSkills
{
    public function getPassiveSkills(Character $character): Collection
    {
        $passiveSkills = PassiveSkill::where('is_parent', true)->get();
        $characterPassives = $character->passiveSkills()
            ->whereIn('passive_skill_id', $passiveSkills->pluck('id'))
            ->with('children')
            ->get()
            ->keyBy('passive_skill_id');
        $allCharacterPassives = collect();
        $collectNestedPassives = function (Collection $passives) use (&$collectNestedPassives, $allCharacterPassives): void {
            foreach ($passives as $passive) {
                $allCharacterPassives->push($passive);
                $collectNestedPassives($passive->children);
            }
        };
        $collectNestedPassives($characterPassives->values());
        (new EloquentCollection($allCharacterPassives->all()))->loadMissing('passiveSkill');
        $questsByPassiveSkillId = Quest::whereIn('unlocks_passive_id', $allCharacterPassives->pluck('passive_skill_id')->unique())
            ->orderBy('id')
            ->get()
            ->groupBy('unlocks_passive_id')
            ->map(fn (EloquentCollection $quests): Quest => $quests->first());
        $completedQuestIds = $character->questsCompleted()
            ->whereIn('quest_id', $questsByPassiveSkillId->pluck('id'))
            ->pluck('quest_id')
            ->flip();

        return $passiveSkills
            ->map(fn (PassiveSkill $passiveSkill) => $characterPassives->get($passiveSkill->id))
            ->filter()
            ->map(fn (CharacterPassiveSkill $characterPassive) => $this->transformNestedPassives($characterPassive, $questsByPassiveSkillId, $completedQuestIds))
            ->values();
    }

    public function getPassiveInTraining(Character $character): ?CharacterPassiveSkill
    {
        return $character->passiveSkills()->whereNotNull('started_at')->first();
    }

    protected function transformNestedPassives(CharacterPassiveSkill $passiveSkill, Collection $questsByPassiveSkillId, Collection $completedQuestIds)
    {

        $passiveSkill = $this->assignQuestInfoToPassive($passiveSkill, $questsByPassiveSkillId, $completedQuestIds);

        if (is_null($passiveSkill->parent_skill_id)) {
            $passiveSkill->name = $passiveSkill->passiveSkill->name;
            $passiveSkill->max_level = $passiveSkill->passiveSkill->max_level;
        }

        if ($passiveSkill->children->isNotEmpty()) {
            foreach ($passiveSkill->children as $child) {
                $child = $this->assignQuestInfoToPassive($child, $questsByPassiveSkillId, $completedQuestIds);
                $child->name = $child->passiveSkill->name;
                $child->max_level = $child->passiveSkill->max_level;

                if ($child->children->isNotEmpty()) {
                    $this->transformNestedPassives($child, $questsByPassiveSkillId, $completedQuestIds);
                }
            }
        }

        return $passiveSkill;
    }

    protected function assignQuestInfoToPassive(CharacterPassiveSkill $passiveSkill, Collection $questsByPassiveSkillId, Collection $completedQuestIds): CharacterPassiveSkill
    {
        $requiredQuest = $questsByPassiveSkillId->get($passiveSkill->passiveSkill->id);

        if (! is_null($requiredQuest)) {
            $passiveSkill->quest_name = $requiredQuest->name;
            $passiveSkill->is_quest_complete = $completedQuestIds->has($requiredQuest->id);
        } else {
            $passiveSkill->quest_name = null;
            $passiveSkill->is_quest_complete = false;
        }

        return $passiveSkill;
    }

    protected function getQuest(int $passiveSkillId): ?Quest
    {
        return Quest::where('unlocks_passive_id', $passiveSkillId)->orderBy('id')->first();
    }
}
