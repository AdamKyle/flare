<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Models\Character;
use App\Flare\Transformers\BasicSkillsTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Events\UpdateCharacterSkills;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class UpdateCharacterSkillsService {

    /**
     * @var SkillService $skillService
     */
    private SkillService $skillService;

    /**
     * @param SkillService $skillService
     */
    public function __construct(SkillService $skillService) {
        $this->skillService = $skillService;
    }

    /**
     * Fire off an event to update the character skills.
     *
     * @param Character $character
     * @return void
     */
    public function updateCharacterSkills(Character $character): void {
        $trainableSkillIds = GameSkill::where('can_train', true)->pluck('id')->toArray();

        $trainingSkills = $this->skillService->getSkills($character, $trainableSkillIds);

        event(new UpdateCharacterSkills($character->user, $trainingSkills));
    }
}
