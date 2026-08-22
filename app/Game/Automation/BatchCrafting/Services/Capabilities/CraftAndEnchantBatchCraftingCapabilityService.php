<?php

namespace App\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantExperienceTargetService;
use App\Game\Automation\BatchCrafting\Services\CraftExperienceTargetService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;

class CraftAndEnchantBatchCraftingCapabilityService
{
    public function __construct(
        private readonly CraftExperienceTargetService $craftExperienceTargetService,
        private readonly CraftAndEnchantExperienceTargetService $craftAndEnchantExperienceTargetService,
        private readonly EnchantingService $enchantingService,
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Build the complete Craft and Enchant capability facts for the character.
     *
     * @param  Character  $character  The character requesting capability facts.
     * @return array The Craft and Enchant capability facts payload.
     */
    public function build(Character $character): array
    {
        $enchantingSkill = $this->enchantingService->findEnchantingSkill($character);
        $canCraftAndEnchant = $this->canCraftAndEnchant($character, $enchantingSkill);

        return [
            'can_craft_and_enchant' => $canCraftAndEnchant,
            'can_craft_and_enchant_for_experience' => $canCraftAndEnchant && $this->craftAndEnchantExperienceTargetService->hasMeaningfulWork($character),
            'enchanting_skill' => $this->enchantingSkillFacts($enchantingSkill),
        ];
    }

    /**
     * Build the factual current Enchanting skill progress for the character, used by the Experience/Event runtime UI.
     *
     * @param  Character  $character  The character being checked.
     * @return array|null The Enchanting skill progress facts, or null when the skill does not exist.
     */
    public function enchantingSkillFactsFor(Character $character): ?array
    {
        return $this->enchantingSkillFacts($this->enchantingService->findEnchantingSkill($character));
    }

    /**
     * Determine whether the character has the real Crafting and Enchanting prerequisites to use this workflow at all.
     *
     * @param  Character  $character  The character being checked.
     * @param  Skill|null  $enchantingSkill  The character's already-resolved Enchanting skill, when present.
     * @return bool True when the character has at least one Crafting skill and an Enchanting skill.
     */
    private function canCraftAndEnchant(Character $character, ?Skill $enchantingSkill): bool
    {
        if (is_null($enchantingSkill)) {
            return false;
        }

        $skills = $this->craftExperienceTargetService->resolveCraftingSkills($character);

        return $skills->filter(fn (?Skill $skill): bool => ! is_null($skill))->isNotEmpty();
    }

    /**
     * Build the factual current Enchanting skill progress used by the setup/runtime UI.
     *
     * @param  Skill|null  $skill  The character's already-resolved Enchanting skill, when present.
     * @return array|null The Enchanting skill progress facts, or null when the skill does not exist.
     */
    private function enchantingSkillFacts(?Skill $skill): ?array
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
