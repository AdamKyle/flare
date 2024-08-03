<?php

namespace App\Game\Skills\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkillProgression;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class UpdateItemSkill
{
    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes;

    public function __construct(UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes)
    {
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    public function updateItemSkill(Character $character, Item $item): void
    {
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

        $this->levelUpSkill($character, $skillProgressionToUpdate);
    }

    protected function levelUpSkill(Character $character, ItemSkillProgression $itemSkillProgression)
    {
        if ($itemSkillProgression->current_kill >= $itemSkillProgression->itemSkill->total_kills_needed) {
            $itemSkillProgression->update([
                'current_level' => $itemSkillProgression->current_level + 1,
                'current_kill' => 0,
            ]);

            $character = $character->refresh();
            $itemSkillProgression = $itemSkillProgression->refresh();

            $this->updateCharacterAttackTypes->updateCache($character->refresh());

            ServerMessageHandler::sendBasicMessage($character->user,
                'Your equipped artifacts: '.$itemSkillProgression->item->affix_name.'\'s Skill: '.$itemSkillProgression->itemSkill->name.' has gained a new level and is now level: '.$itemSkillProgression->current_level.'.'
            );
        }
    }
}
