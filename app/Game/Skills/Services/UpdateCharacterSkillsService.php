<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Events\UpdateCharacterSkills;

class UpdateCharacterSkillsService
{
    private SkillService $skillService;

    public function __construct(SkillService $skillService)
    {
        $this->skillService = $skillService;
    }

    /**
     * Fire off an event to update the character training skills.
     */
    public function updateCharacterSkills(Character $character): void
    {
        $trainableSkillIds = GameSkill::where('can_train', true)->pluck('id')->toArray();

        $trainingSkills = $this->skillService->getSkills($character, $trainableSkillIds);

        event(new UpdateCharacterSkills($character->user, $trainingSkills));
    }

    /**
     * Fire off an event to update the character crafting skills.
     */
    public function updateCharacterCraftingSkills(Character $character): void
    {
        $trainableSkillIds = GameSkill::where('can_train', false)->pluck('id')->toArray();

        $craftingSkills = $this->skillService->getSkills($character, $trainableSkillIds);

        event(new UpdateCharacterSkills($character->user, [], $craftingSkills));
    }
}
