<?php

namespace App\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;

class EnchantBatchCraftingCapabilityService
{
    public function __construct(
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
    ) {}

    /**
     * Build the complete Enchant For Event capability facts for the character.
     *
     * @param Character $character The character requesting capability facts.
     * @return array The Enchant For Event capability facts payload.
     */
    public function build(Character $character): array
    {
        return [
            'can_enchant_for_event' => $this->canEnchantForEvent($character),
            'enchant_event_goal' => $this->eventGoalFacts($character),
        ];
    }

    /**
     * Determine whether Enchant For Event is currently available to the character.
     *
     * @param Character $character The character being checked.
     * @return bool True when a real eligible Enchant Event goal currently exists.
     */
    public function canEnchantForEvent(Character $character): bool
    {
        return $this->globalEventGoalEligibilityService->canEnchantForEvent($character);
    }

    /**
     * Build the factual current Enchant Event goal facts, when a real eligible goal exists.
     *
     * @param Character $character The character being checked.
     * @return array|null The current Enchant Event goal facts, or null when none is eligible.
     */
    public function eventGoalFacts(Character $character): ?array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            return null;
        }

        return $this->buildGoalFacts($character, $goal);
    }

    /**
     * Build the factual goal/contribution facts for a known Enchant Event goal id.
     *
     * Used to preserve the final goal/contribution facts after the goal is no longer
     * currently eligible (completed, or the Event stepped away from Enchanting).
     *
     * @param Character $character The character being checked.
     * @param int $goalId The authoritative persisted Enchant Event goal id.
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
     * Build the factual goal/contribution payload for a resolved Enchant Event goal.
     *
     * @param Character $character The character being checked.
     * @param GlobalEventGoal $goal The resolved Enchant Event goal.
     * @return array The goal/contribution facts payload.
     */
    private function buildGoalFacts(Character $character, GlobalEventGoal $goal): array
    {
        $contribution = $character->globalEventEnchants()
            ->where('global_event_goal_id', $goal->id)
            ->first()?->enchants ?? 0;

        $event = $goal->event;

        return [
            'goal_id' => $goal->id,
            'event_id' => $event?->id,
            'event_type' => $event?->type,
            'max_enchants' => $goal->max_enchants,
            'total_enchants' => $goal->total_enchants,
            'remaining_enchants' => max(0, $goal->max_enchants - $goal->total_enchants),
            'next_reward_at' => $goal->next_reward_at,
            'reward_every' => $goal->reward_every,
            'character_contribution' => $contribution,
            'ends_at' => $event?->ends_at,
        ];
    }
}
