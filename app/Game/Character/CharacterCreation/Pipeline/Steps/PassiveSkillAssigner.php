<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
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
        $questsThatUnlockPassives = Quest::whereIn('unlocks_passive_id', $passiveSkills->pluck('id')->toArray())->pluck('id', 'unlocks_passive_id')->toArray();

        $passivesToInsert = collect();

        foreach ($passiveSkills as $passiveSkill) {

            $parentSkill = null;

            if (! is_null($passiveSkill->parent_skill_id)) {
                $parentSkill = $passiveSkill->parent;
            }

            $passivesToInsert->add([
                'character_id' => $character->id,
                'passive_skill_id' => $passiveSkill->id,
                'current_level' => 0,
                'hours_to_next' => $passiveSkill->hours_per_level,
                'is_locked' => $this->getIsSkillLocked($passiveSkill, $questsThatUnlockPassives),
                'parent_skill_id' => ! is_null($parentSkill) ? $parentSkill->id : null,
            ]);
        }

        $passivesToInsert->chunk(100)->each(function ($passiveSkillsChunk) use ($character) {
            $character->passiveSkills()->insert($passiveSkillsChunk->values()->all());
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
