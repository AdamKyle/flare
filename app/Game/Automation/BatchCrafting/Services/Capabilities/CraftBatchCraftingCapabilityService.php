<?php

namespace App\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Automation\BatchCrafting\Services\CraftExperienceTargetService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Skills\Services\CraftingService;
use Illuminate\Support\Collection as SupportCollection;

class CraftBatchCraftingCapabilityService
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
        private readonly CraftExperienceTargetService $craftExperienceTargetService,
    ) {}

    /**
     * Build the complete Craft capability facts for the character.
     *
     * @param Character $character The character requesting capability facts.
     * @return array The Craft capability facts payload.
     */
    public function build(Character $character): array
    {
        $craftingSkills = $this->craftExperienceTargetService->resolveCraftingSkills($character);

        return [
            'can_craft_for_experience' => $this->craftExperienceTargetService->hasMeaningfulTarget($craftingSkills),
            'can_craft_for_event' => $this->canCraftForEvent($character),
            'crafting_skills' => $this->buildCraftingSkillFacts($craftingSkills),
            'event_goal' => $this->eventGoalFacts($character),
        ];
    }

    /**
     * Determine whether Craft For Experience is currently available to the character.
     *
     * @param Character $character The character being checked.
     * @return bool True when at least one meaningful Experience cycle target currently exists.
     */
    public function canCraftForExperience(Character $character): bool
    {
        $skills = $this->craftExperienceTargetService->resolveCraftingSkills($character);

        return $this->craftExperienceTargetService->hasMeaningfulTarget($skills);
    }

    /**
     * Determine whether Craft For Event is currently available to the character.
     *
     * @param Character $character The character being checked.
     * @return bool True when a real eligible Craft Event goal currently exists.
     */
    public function canCraftForEvent(Character $character): bool
    {
        return $this->globalEventGoalEligibilityService->canCraftForEvent($character);
    }

    /**
     * Build the factual current Crafting skill progress used by the Experience runtime UI.
     *
     * @param Character $character The character being checked.
     * @return array<int, array> The four Crafting skill progress facts.
     */
    public function craftingSkillFacts(Character $character): array
    {
        $skills = $this->craftExperienceTargetService->resolveCraftingSkills($character);

        return $this->buildCraftingSkillFacts($skills);
    }

    /**
     * Transform already-resolved Crafting skills into the Experience runtime UI's skill progress facts.
     *
     * @param SupportCollection $skills The character's already-resolved Crafting skills.
     * @return array<int, array> The four Crafting skill progress facts.
     */
    private function buildCraftingSkillFacts(SupportCollection $skills): array
    {
        $facts = [];

        foreach ($skills as $group => $skill) {
            if (is_null($skill)) {
                continue;
            }

            $facts[] = [
                'crafting_type' => $group,
                'skill_name' => $skill->name,
                'level' => $skill->level,
                'max_level' => $skill->max_level,
                'current_xp' => $skill->xp,
                'next_level_xp' => $skill->xp_max,
                'is_maxed' => $this->craftingService->isSkillMaxed($skill),
            ];
        }

        return $facts;
    }

    /**
     * Build the factual current Craft Event goal facts, when a real eligible goal exists.
     *
     * @param Character $character The character being checked.
     * @return array|null The current Craft Event goal facts, or null when none is eligible.
     */
    public function eventGoalFacts(Character $character): ?array
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            return null;
        }

        return $this->buildGoalFacts($character, $goal);
    }

    /**
     * Build the factual goal/contribution facts for a known Craft Event goal id.
     *
     * Used to preserve the final goal/contribution facts after the goal is no longer
     * currently eligible (completed, or the Event stepped away from Crafting).
     *
     * @param Character $character The character being checked.
     * @param int $goalId The authoritative persisted Craft Event goal id.
     * @return array|null The goal/contribution facts payload, or null when the goal no longer exists.
     */
    public function eventGoalFactsById(Character $character, int $goalId): ?array
    {
        $goal = $this->globalEventGoalEligibilityService->findGoalById($goalId);

        if (is_null($goal)) {
            return null;
        }

        return $this->buildGoalFacts($character, $goal);
    }

    /**
     * Build the factual goal/contribution payload for a resolved Craft Event goal.
     *
     * @param Character $character The character being checked.
     * @param GlobalEventGoal $goal The resolved Craft Event goal.
     * @return array The goal/contribution facts payload.
     */
    private function buildGoalFacts(Character $character, GlobalEventGoal $goal): array
    {
        $contribution = $character->globalEventCrafts()
            ->where('global_event_goal_id', $goal->id)
            ->first()?->crafts ?? 0;

        return [
            'goal_id' => $goal->id,
            'max_crafts' => $goal->max_crafts,
            'total_crafts' => $goal->total_crafts,
            'next_reward_at' => $goal->next_reward_at,
            'reward_every' => $goal->reward_every,
            'character_contribution' => $contribution,
        ];
    }
}
