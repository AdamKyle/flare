<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Values\ResolvedCraftAndEnchantExperienceTarget;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\CraftingSkillGroup;
use Illuminate\Support\Collection as SupportCollection;

class CraftAndEnchantExperienceTargetService
{
    public function __construct(
        private readonly CraftExperienceTargetService $craftExperienceTargetService,
        private readonly EnchantingService $enchantingService,
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Determine whether meaningful Craft and Enchant For Experience work currently exists.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when at least one side of the workflow still offers meaningful progression.
     */
    public function hasMeaningfulWork(Character $character): bool
    {
        return ! is_null($this->resolveNext($character, 0));
    }

    /**
     * Resolve the next base item to craft for this Experience operation.
     *
     * Prefers the real Crafting Experience cycle target so both sides progress together.
     * When every Crafting discipline is maxed or offers nothing meaningful, falls back to
     * any real currently craftable item so Enchanting progression can continue on its own,
     * but only while the Enchanting skill itself is not maxed and a meaningful affix exists.
     *
     * @param  Character  $character  The character running the batch.
     * @param  int  $cyclePosition  The persisted Crafting Experience cycle position to search from.
     * @return ResolvedCraftAndEnchantExperienceTarget|null The resolved target, or null when no meaningful work exists.
     */
    public function resolveNext(Character $character, int $cyclePosition): ?ResolvedCraftAndEnchantExperienceTarget
    {
        $skills = $this->craftExperienceTargetService->resolveCraftingSkills($character);
        $craftTarget = $this->craftExperienceTargetService->resolveNextTarget($skills, $cyclePosition);

        if (! is_null($craftTarget)) {
            $nextPosition = ($craftTarget->index + 1) % $this->craftExperienceTargetService->cycleSize();

            return new ResolvedCraftAndEnchantExperienceTarget($craftTarget->item, $craftTarget->target->skillGroup, $nextPosition);
        }

        return $this->resolveEnchantingOnlyFallbackTarget($character, $skills, $cyclePosition);
    }

    /**
     * Resolve a real currently craftable base item purely to support Enchanting progression,
     * once the Crafting Experience cycle itself has nothing meaningful left to offer.
     *
     * Resolves every Crafting discipline's cheapest currently craftable item in a single bounded
     * query, before selecting between them, so no query runs from inside the group loop.
     *
     * @param  Character  $character  The character running the batch.
     * @param  SupportCollection<string, Skill|null>  $skills  The character's already-resolved Crafting skills.
     * @param  int  $cyclePosition  The persisted Crafting Experience cycle position to resume at.
     * @return ResolvedCraftAndEnchantExperienceTarget|null The resolved fallback target, or null when unavailable.
     */
    private function resolveEnchantingOnlyFallbackTarget(Character $character, SupportCollection $skills, int $cyclePosition): ?ResolvedCraftAndEnchantExperienceTarget
    {
        $enchantingSkill = $this->enchantingService->findEnchantingSkill($character);

        if (is_null($enchantingSkill) || $this->craftingService->isSkillMaxed($enchantingSkill)) {
            return null;
        }

        $affixes = $this->enchantingService->findMeaningfulBatchAffixes($character);

        if (is_null($affixes['prefix']) && is_null($affixes['suffix'])) {
            return null;
        }

        $candidatesByGroup = $this->craftingService->findInexpensiveCraftableItemsForGroups($skills);

        foreach (CraftingSkillGroup::cases() as $group) {
            $item = $candidatesByGroup->get($group->value);

            if (! is_null($item)) {
                return new ResolvedCraftAndEnchantExperienceTarget($item, $group, $cyclePosition);
            }
        }

        return null;
    }
}
