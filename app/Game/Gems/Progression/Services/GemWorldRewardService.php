<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\CharacterCurrencyRewardService;
use App\Game\Gems\Progression\Values\GemProgressionBands;
use App\Game\Gems\Progression\Values\GemScrollAggregate;
use App\Game\Gems\Progression\Values\GemWorldRewardPlan;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;
use Illuminate\Support\Facades\DB;

class GemWorldRewardService
{
    /**
     * @param GemWorldProfileResolver $gemWorldProfileResolver
     * @param GemProgressionService $gemProgressionService
     * @param GemScrollEffectService $gemScrollEffectService
     * @param GemWorldRewardPlanService $gemWorldRewardPlanService
     * @param GemWorldRewardDeliveryService $gemWorldRewardDeliveryService
     * @param BattleRewardLedgerService $battleRewardLedgerService
     * @param BattleRewardMessageOutboxService $battleRewardMessageOutboxService
     * @param CharacterCurrencyRewardService $characterCurrencyRewardService
     * @param GemProgressionBroadcastService $gemProgressionBroadcastService
     */
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemProgressionService $gemProgressionService,
        private readonly GemScrollEffectService $gemScrollEffectService,
        private readonly GemWorldRewardPlanService $gemWorldRewardPlanService,
        private readonly GemWorldRewardDeliveryService $gemWorldRewardDeliveryService,
        private readonly BattleRewardLedgerService $battleRewardLedgerService,
        private readonly BattleRewardMessageOutboxService $battleRewardMessageOutboxService,
        private readonly CharacterCurrencyRewardService $characterCurrencyRewardService,
        private readonly GemProgressionBroadcastService $gemProgressionBroadcastService,
    ) {}

    /**
     * Apply the Gem World reward step for one battle reward request, or a no-op result when not inside a generated Gem World.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param Character $character
     * @param array $effectiveMonster
     * @param int $qualifyingKills
     * @param array $earnedCurrencies
     * @return array
     */
    public function applyToLedgerStep(
        CharacterBattleRewardRequestStep $step,
        Character $character,
        array $effectiveMonster,
        int $qualifyingKills,
        array $earnedCurrencies,
    ): array {
        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile)) {
            return ['applied' => false, 'reason' => 'not_a_gem_world'];
        }

        $checkpoint = $step->checkpoint_json ?? [];

        if (! ($checkpoint['xp_applied'] ?? false)) {
            [$step, $checkpoint] = $this->applyXpPhase($step, $checkpoint, $character, $resolvedProfile, $effectiveMonster, $qualifyingKills);
        }

        if (! ($checkpoint['reward_plan'] ?? null)) {
            [$step, $checkpoint] = $this->planRewardsPhase($step, $checkpoint, $character, $resolvedProfile, $qualifyingKills);
        }

        if (! ($checkpoint['rewards_delivered'] ?? false)) {
            [$step, $checkpoint] = $this->deliverRewardsPhase($step, $checkpoint, $character);
        }

        if (! ($checkpoint['currency_scroll_applied'] ?? false)) {
            [$step, $checkpoint] = $this->applyCurrencyScrollBonusPhase($step, $checkpoint, $character, $resolvedProfile, $earnedCurrencies);
        }

        if (! ($checkpoint['messages_stored'] ?? false)) {
            [$step, $checkpoint] = $this->storeLostRewardMessagesPhase($step, $checkpoint, $character);
        }

        $this->gemProgressionBroadcastService->broadcastForProfile(
            $character->fresh(),
            $resolvedProfile,
            $checkpoint['global_level'],
            $checkpoint['global_xp'],
            $checkpoint['personal_level'],
            $checkpoint['personal_xp'],
        );

        return [
            'applied' => true,
            'profile_type' => $resolvedProfile->type()->value,
            'profile_id' => $resolvedProfile->profileId(),
            'personal_level' => $checkpoint['personal_level'],
        ];
    }

    /**
     * Apply global and personal Gem progression XP and its ledger checkpoint atomically.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param array $checkpoint
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param array $effectiveMonster
     * @param int $qualifyingKills
     * @return array
     */
    private function applyXpPhase(
        CharacterBattleRewardRequestStep $step,
        array $checkpoint,
        Character $character,
        ResolvedGemWorldProfile $resolvedProfile,
        array $effectiveMonster,
        int $qualifyingKills,
    ): array {
        return DB::transaction(function () use ($step, $checkpoint, $character, $resolvedProfile, $effectiveMonster, $qualifyingKills): array {
            $checkpoint = array_merge($checkpoint, $this->applyGemXp($character, $resolvedProfile, $effectiveMonster, $qualifyingKills));
            $checkpoint['xp_applied'] = true;
            $step = $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);

            return [$step, $checkpoint];
        });
    }

    /**
     * Apply global and personal Gem progression XP for this reward request.
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param array $effectiveMonster
     * @param int $qualifyingKills
     * @return array
     */
    private function applyGemXp(Character $character, ResolvedGemWorldProfile $resolvedProfile, array $effectiveMonster, int $qualifyingKills): array
    {
        $baseGemXpPerKill = intval(round($effectiveMonster['xp'] * GemProgressionBands::GEM_SCROLL_BASE_XP_MULTIPLIER));
        $globalXpTotal = $baseGemXpPerKill * $qualifyingKills;

        $xpScrollBonus = $this->resolveScrollAggregate($character, $resolvedProfile)->xpBonusTotal();
        $personalXpTotal = intval(round($globalXpTotal * (1 + $xpScrollBonus)));

        if ($resolvedProfile->isMapProfile()) {
            $globalResult = $this->gemProgressionService->applyGlobalMapProgressionXp($resolvedProfile->mapProfile(), $globalXpTotal);
            $personalResult = $this->gemProgressionService->applyPersonalMapProgressionXp($character, $resolvedProfile->mapProfile(), $personalXpTotal);
        } else {
            $globalResult = $this->gemProgressionService->applyGlobalLocationProgressionXp($resolvedProfile->locationProfile(), $globalXpTotal);
            $personalResult = $this->gemProgressionService->applyPersonalLocationProgressionXp($character, $resolvedProfile->locationProfile(), $personalXpTotal);
        }

        return [
            'personal_level' => $personalResult->newLevel(),
            'personal_xp' => $personalResult->newXp(),
            'global_level' => $globalResult->newLevel(),
            'global_xp' => $globalResult->newXp(),
            'global_leveled_up' => $globalResult->leveledUp(),
            'personal_leveled_up' => $personalResult->leveledUp(),
        ];
    }

    /**
     * Roll and checkpoint the complete random reward plan for this reward request.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param array $checkpoint
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param int $qualifyingKills
     * @return array
     */
    private function planRewardsPhase(
        CharacterBattleRewardRequestStep $step,
        array $checkpoint,
        Character $character,
        ResolvedGemWorldProfile $resolvedProfile,
        int $qualifyingKills,
    ): array {
        $scrollAggregate = $this->resolveScrollAggregate($character->fresh(), $resolvedProfile);
        $plan = $this->gemWorldRewardPlanService->plan($checkpoint['personal_level'], $qualifyingKills, $scrollAggregate);

        $checkpoint['reward_plan'] = $plan->toArray();
        $step = $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);

        return [$step, $checkpoint];
    }

    /**
     * Deliver every planned reward and mark the delivery phase complete.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param array $checkpoint
     * @param Character $character
     * @return array
     */
    private function deliverRewardsPhase(CharacterBattleRewardRequestStep $step, array $checkpoint, Character $character): array
    {
        $plan = GemWorldRewardPlan::fromArray($checkpoint['reward_plan']);

        $this->gemWorldRewardDeliveryService->deliver($step->fresh(), $character->fresh(), $plan, $checkpoint);

        $step = $step->fresh();
        $checkpoint = $step->checkpoint_json ?? $checkpoint;
        $checkpoint['rewards_delivered'] = true;
        $step = $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);

        return [$step, $checkpoint];
    }

    /**
     * Apply the active Currency Scroll bonus on top of the completed `CURRENCY_REWARDS` step's ledger-recovered currency amounts.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param array $checkpoint
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param array $earnedCurrencies
     * @return array
     */
    private function applyCurrencyScrollBonusPhase(
        CharacterBattleRewardRequestStep $step,
        array $checkpoint,
        Character $character,
        ResolvedGemWorldProfile $resolvedProfile,
        array $earnedCurrencies,
    ): array {
        $completedCurrencyStep = $this->battleRewardLedgerService->completedStepResult($step->request, BattleRewardStepName::CURRENCY_REWARDS);
        $currencies = $completedCurrencyStep['currencies'] ?? $earnedCurrencies;

        $scrollAggregate = $this->resolveScrollAggregate($character->fresh(), $resolvedProfile);
        $bonusAmounts = [
            'gold' => $this->currencyScrollBonusAmount($currencies['gold'] ?? 0, $scrollAggregate->goldBonusTotal()),
            'gold_dust' => $this->currencyScrollBonusAmount($currencies['gold_dust'] ?? 0, $scrollAggregate->goldDustBonusTotal()),
            'shards' => $this->currencyScrollBonusAmount($currencies['shards'] ?? 0, $scrollAggregate->shardsBonusTotal()),
            'copper_coins' => $this->currencyScrollBonusAmount($currencies['copper_coins'] ?? 0, $scrollAggregate->copperCoinBonusTotal()),
        ];

        $checkpoint['currency_scroll_result'] = $this->characterCurrencyRewardService->applyGemScrollBonus($character->fresh(), $bonusAmounts);
        $checkpoint['currency_scroll_applied'] = true;
        $step = $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);

        return [$step, $checkpoint];
    }

    /**
     * Resolve the extra currency amount granted by an active Currency Scroll bonus.
     *
     * @param int $baseAmount
     * @param float $bonusRatio
     * @return int
     */
    private function currencyScrollBonusAmount(int $baseAmount, float $bonusRatio): int
    {
        if ($bonusRatio <= 0.0 || $baseAmount <= 0) {
            return 0;
        }

        return intval(round($baseAmount * $bonusRatio));
    }

    /**
     * Store a one-time warning message for every reward lost to a full Alchemy Bag/inventory or a currency cap this request.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param array $checkpoint
     * @param Character $character
     * @return array
     */
    private function storeLostRewardMessagesPhase(CharacterBattleRewardRequestStep $step, array $checkpoint, Character $character): array
    {
        foreach ($this->buildLostRewardMessages($checkpoint) as $message) {
            $this->battleRewardMessageOutboxService->storeMessage(
                $step->character_battle_reward_request_id,
                $character->id,
                $character->user_id,
                $step->step_name->value,
                $message,
            );
        }

        $checkpoint['messages_stored'] = true;
        $step = $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);

        return [$step, $checkpoint];
    }

    /**
     * Build the factual lost-reward warning messages for this request's checkpointed results.
     *
     * @param array $checkpoint
     * @return array
     */
    private function buildLostRewardMessages(array $checkpoint): array
    {
        $messages = [];
        $tally = $checkpoint['rewards_tally'] ?? [];
        $currencyResult = $checkpoint['currency_scroll_result'] ?? [];

        if (($tally['scrolls_lost_to_full_bag'] ?? 0) > 0) {
            $messages[] = 'Your Alchemy Bag was full: '.$tally['scrolls_lost_to_full_bag'].' Gem Scroll reward(s) were lost.';
        }

        $lostItems = ($tally['enhanced_items_lost_to_full_inventory'] ?? 0) + ($tally['item_opportunity_items_lost_to_full_inventory'] ?? 0);

        if ($lostItems > 0) {
            $messages[] = 'Your inventory was full: '.$lostItems.' Gem World item reward(s) were lost.';
        }

        foreach (['gold' => 'Gold', 'gold_dust' => 'Gold Dust', 'shards' => 'Shards', 'copper_coins' => 'Copper Coins'] as $currency => $label) {
            $wasted = $currencyResult[$currency]['wasted'] ?? 0;

            if ($wasted > 0) {
                $messages[] = 'You reached the '.$label.' cap: '.$wasted.' '.$label.' from your Gem Scroll bonus was lost.';
            }
        }

        return $messages;
    }

    /**
     * Resolve the active Gem Scroll aggregate for the Character's current exact profile.
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @return GemScrollAggregate
     */
    private function resolveScrollAggregate(Character $character, ResolvedGemWorldProfile $resolvedProfile): GemScrollAggregate
    {
        if ($resolvedProfile->isMapProfile()) {
            return $this->gemScrollEffectService->aggregateForMapProfile($character, $resolvedProfile->mapProfile());
        }

        return $this->gemScrollEffectService->aggregateForLocationProfile($character, $resolvedProfile->locationProfile());
    }
}
