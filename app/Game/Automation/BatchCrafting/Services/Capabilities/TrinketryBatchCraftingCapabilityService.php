<?php

namespace App\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\TrinketCraftingService;

class TrinketryBatchCraftingCapabilityService
{
    public function __construct(
        private readonly TrinketCraftingService $trinketCraftingService,
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Build the complete Trinketry capability facts for the character.
     *
     * @param Character $character The character requesting capability facts.
     * @return array The Trinketry capability facts payload.
     */
    public function build(Character $character): array
    {
        $skill = $this->trinketCraftingService->findTrinketrySkill($character);
        $canTrinketry = ! is_null($skill) && ! is_null($this->trinketCraftingService->findMeaningfulBatchItem($character));

        return [
            'can_trinketry' => $canTrinketry,
            'trinketry_skill' => $this->trinketrySkillFacts($skill),
        ];
    }

    /**
     * Build the factual current Trinketry skill progress used by the setup/runtime UI.
     *
     * @param Skill|null $skill The character's already-resolved Trinketry skill, when present.
     * @return array|null The Trinketry skill progress facts, or null when the skill does not exist.
     */
    private function trinketrySkillFacts(?Skill $skill): ?array
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
