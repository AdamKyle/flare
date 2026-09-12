<?php

namespace App\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;

class AlchemyBatchCraftingCapabilityService
{
    public function __construct(
        private readonly AlchemyService $alchemyService,
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Build the complete Alchemy capability facts for the character.
     *
     * @param Character $character The character requesting capability facts.
     * @return array The Alchemy capability facts payload.
     */
    public function build(Character $character): array
    {
        $skill = $this->alchemyService->findAlchemySkill($character);
        $canAlchemy = ! is_null($skill);

        return [
            'can_alchemy' => $canAlchemy,
            'can_alchemy_for_experience' => $canAlchemy && $this->hasMeaningfulTarget($character),
            'alchemy_skill' => $this->alchemySkillFacts($skill),
        ];
    }

    /**
     * Determine whether at least one meaningful Alchemy Experience target currently exists.
     *
     * @param Character $character The character being checked.
     * @return bool True when a meaningful target currently exists.
     */
    private function hasMeaningfulTarget(Character $character): bool
    {
        return ! is_null($this->alchemyService->findMeaningfulBatchItem($character));
    }

    /**
     * Build the factual current Alchemy skill progress used by the setup/runtime UI.
     *
     * @param Skill|null $skill The character's already-resolved Alchemy skill, when present.
     * @return array|null The Alchemy skill progress facts, or null when the skill does not exist.
     */
    private function alchemySkillFacts(?Skill $skill): ?array
    {
        if (is_null($skill)) {
            return null;
        }

        return [
            'skill_name' => $skill->name,
            'level' => $skill->level,
            'max_level' => $skill->max_level,
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'is_maxed' => $this->craftingService->isSkillMaxed($skill),
        ];
    }
}
