<?php

namespace App\Game\Skill\Handlers;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Models\ItemSkillProgression;

class UpdateItemSkill {

    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    public function __construct(UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    public function updateItemSkill(Character $character, Item $item): void {
        $skillProgressionToUpdate = $item->itemSkillProgressions->where('is_training', true)->first();

        if (is_null($skillProgressionToUpdate)) {
            return;
        }

        if ($skillProgressionToUpdate->current_level >= $skillProgressionToUpdate->itemSkill->max_level) {
            return;
        }

        $skillProgressionToUpdate->update([
            'current_kill' => $skillProgressionToUpdate->current_kill + 1,
        ]);

        $skillProgressionToUpdate = $skillProgressionToUpdate->refresh();
    }

    protected function levelUpSkill(Character $character, ItemSkillProgression $itemSkillProgression) {
        if ($itemSkillProgression->current_kills >= $itemSkillProgression->itemSkill->total_kills_needed) {
            $itemSkillProgression->update([
                'current_level' => $itemSkillProgression->current_level + 1
            ]);

            $this->updateCharacterAttackTypes->updateCache($character->refresh());
            
        }
    }
}