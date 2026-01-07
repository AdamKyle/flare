<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;

class PassiveSkillAssigner
{
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character === null) {
            return $next($state);
        }

        $this->assignPassiveSkills($character);

        return $next($state);
    }

    private function assignPassiveSkills(Character $character): void
    {
        $passiveSkills = PassiveSkill::with('parent')->get();
        $questsThatUnlockPassives = Quest::whereIn('unlocks_passive_id', $passiveSkills->pluck('id')->toArray())
            ->pluck('id', 'unlocks_passive_id')
            ->toArray();

        $passivesToInsert = collect();

        foreach ($passiveSkills as $passiveSkill) {
            $passivesToInsert->add([
                'character_id' => $character->id,
                'passive_skill_id' => $passiveSkill->id,
                'current_level' => 0,
                'hours_to_next' => $passiveSkill->hours_per_level,
                'is_locked' => $this->getIsSkillLocked($passiveSkill, $questsThatUnlockPassives),
                'parent_skill_id' => null,
            ]);
        }

        $passivesToInsert->chunk(100)->each(function ($passiveSkillsChunk) use ($character) {
            $character->passiveSkills()->insert($passiveSkillsChunk->values()->all());
        });

        $this->updatePassiveParents($character, $passiveSkills);
    }

    private function updatePassiveParents(Character $character, $passiveSkills): void
    {
        $childPassiveSkills = $passiveSkills
            ->whereNotNull('parent_skill_id')
            ->values();

        if ($childPassiveSkills->isEmpty()) {
            return;
        }

        $parentPassiveSkillIds = $childPassiveSkills
            ->pluck('parent_skill_id')
            ->unique()
            ->values();

        $relevantPassiveSkillIds = $childPassiveSkills
            ->pluck('id')
            ->merge($parentPassiveSkillIds)
            ->unique()
            ->values()
            ->all();

        $characterPassiveSkillIdsByPassiveSkillId = $character->passiveSkills()
            ->whereIn('passive_skill_id', $relevantPassiveSkillIds)
            ->get(['id', 'passive_skill_id'])
            ->pluck('id', 'passive_skill_id');

        $childPassiveSkills
            ->groupBy('parent_skill_id')
            ->each(function ($children, $parentPassiveSkillId) use ($characterPassiveSkillIdsByPassiveSkillId) {
                $parentCharacterPassiveSkillId = $characterPassiveSkillIdsByPassiveSkillId->get($parentPassiveSkillId);

                if ($parentCharacterPassiveSkillId === null) {
                    return;
                }

                $childCharacterPassiveSkillIds = $children
                    ->pluck('id')
                    ->map(fn (int $childPassiveSkillId) => $characterPassiveSkillIdsByPassiveSkillId->get($childPassiveSkillId))
                    ->filter()
                    ->values()
                    ->all();

                if (empty($childCharacterPassiveSkillIds)) {
                    return;
                }

                CharacterPassiveSkill::whereIn('id', $childCharacterPassiveSkillIds)
                    ->update(['parent_skill_id' => $parentCharacterPassiveSkillId]);
            });
    }

    private function getIsSkillLocked(PassiveSkill $passiveSkill, $questIdsThatUnlockPassives = []): bool
    {
        $isLocked = $passiveSkill->is_locked;

        $parent = $passiveSkill->parent;

        if (! is_null($parent)) {
            $isLocked = $passiveSkill->unlocks_at_level > $parent->current_level;
        }

        if (isset($questIdsThatUnlockPassives[$passiveSkill->id])) {
            return true;
        }

        return $isLocked;
    }
}
