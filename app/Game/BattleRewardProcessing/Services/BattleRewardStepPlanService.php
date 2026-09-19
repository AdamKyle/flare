<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Values\BattleRewardSharedContext;
use App\Game\BattleRewardProcessing\Values\BattleRewardStepPlan;
use App\Game\Events\Contracts\WinterBattleRewardEligibility;

class BattleRewardStepPlanService
{
    /**
     * @param WinterBattleRewardEligibility $winterBattleRewardEligibility
     */
    public function __construct(
        private readonly WinterBattleRewardEligibility $winterBattleRewardEligibility,
    ) {}

    /**
     * Build the fixed ledger-step plan for a Faction Loyalty reward request.
     *
     * @return BattleRewardStepPlan
     */
    public function planFactionLoyalty(): BattleRewardStepPlan
    {
        return new BattleRewardStepPlan(BattleRewardStepName::orderedForFactionLoyalty());
    }

    /**
     * Build the fixed ledger-step plan for Quest and Guide Quest reward requests.
     *
     * @return BattleRewardStepPlan
     */
    public function planQuest(): BattleRewardStepPlan
    {
        return new BattleRewardStepPlan(BattleRewardStepName::orderedForQuest());
    }

    /**
     * Build the applicable ledger-step plan for a battle-like reward request.
     *
     * @param CharacterBattleRewardRequest $request
     * @param BattleRewardSharedContext $sharedContext
     * @return BattleRewardStepPlan
     */
    public function planBattleLike(CharacterBattleRewardRequest $request, BattleRewardSharedContext $sharedContext): BattleRewardStepPlan
    {
        $isExploration = $request->source_type === BattleRewardRequestSourceType::EXPLORATION;

        $steps = [];

        if ($isExploration) {
            $steps[] = BattleRewardStepName::BUILD_REWARD_PLAN;
        }

        $steps[] = BattleRewardStepName::SKILL_POINTS;
        $steps[] = BattleRewardStepName::FACTION_POINTS;
        $steps[] = BattleRewardStepName::FACTION_LOYALTY_BOUNTY;
        $steps[] = BattleRewardStepName::CURRENCY_REWARDS;

        if ($this->hasSpecialLocationReward($sharedContext)) {
            $steps[] = BattleRewardStepName::SPECIFIC_LOCATION_REWARDS;
        }

        $steps[] = BattleRewardStepName::ITEM_DROPS;

        if ($sharedContext->isWeeklyMonster()) {
            $steps[] = BattleRewardStepName::WEEKLY_REWARDS;
        }

        $steps[] = BattleRewardStepName::SECONDARY_REWARDS;
        $steps[] = BattleRewardStepName::GLOBAL_EVENT_PARTICIPATION;
        $steps[] = BattleRewardStepName::XP;

        if ($sharedContext->isGeneratedGemWorld()) {
            $steps[] = BattleRewardStepName::GEM_WORLD_REWARDS;
        }

        if ($isExploration && ! is_null($sharedContext->explorationLogId())) {
            $steps[] = BattleRewardStepName::EXPLORATION_CONTEXT;
        }

        if ($this->winterBattleRewardEligibility->isEligible($request->character_id)) {
            $steps[] = BattleRewardStepName::WINTER_EVENT;
        }

        $steps[] = BattleRewardStepName::FINAL_PLAYER_UPDATES;
        $steps[] = BattleRewardStepName::MESSAGE_OUTBOX;

        return new BattleRewardStepPlan($steps);
    }

    /**
     * Determine whether the request's already-resolved Location is one of the special reward Locations.
     *
     * @param BattleRewardSharedContext $sharedContext
     * @return bool
     */
    private function hasSpecialLocationReward(BattleRewardSharedContext $sharedContext): bool
    {
        $locationType = $sharedContext->locationType();

        if (is_null($locationType)) {
            return false;
        }

        return $locationType->isPurgatorySmithHouse() || $locationType->isGoldMines() || $locationType->isTheOldChurch();
    }
}
