<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Item;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Progression\Exceptions\GemScrollNotEligibleException;
use App\Game\Gems\Progression\Values\GemProgressionBands;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use App\Game\Gems\Progression\Values\GemScrollRollPlan;
use App\Game\Gems\Progression\Values\GemScrollTierRange;
use App\Game\Gems\Progression\Values\GemScrollTierRanges;
use App\Game\Gems\Progression\Values\GemScrollType;

class GemScrollGenerator
{
    /**
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param GemScrollTierRanges $gemScrollTierRanges
     */
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly GemScrollTierRanges $gemScrollTierRanges,
    ) {}

    /**
     * Plan and immediately persist one Gem Scroll Item for the given
     * personal Gem progression level. Rejects a personal level below the
     * Gem Scroll eligibility level.
     *
     * @param int $personalLevel
     * @return Item
     */
    public function generateForPersonalLevel(int $personalLevel): Item
    {
        return $this->createFromPlan($this->planForPersonalLevel($personalLevel));
    }

    /**
     * Roll every random value a Gem Scroll reward needs for the given
     * personal Gem progression level, without persisting anything. Rejects
     * a personal level below the Gem Scroll eligibility level.
     *
     * @param int $personalLevel
     * @return GemScrollRollPlan
     */
    public function planForPersonalLevel(int $personalLevel): GemScrollRollPlan
    {
        if ($personalLevel < GemProgressionBands::PERSONAL_BASE_CAP_LEVEL) {
            throw GemScrollNotEligibleException::forPersonalLevel($personalLevel);
        }

        $tierRange = $this->gemScrollTierRanges->forPersonalLevel($personalLevel);

        return match ($this->rollScrollType()) {
            GemScrollType::XP => $this->planXpScroll($tierRange),
            GemScrollType::CURRENCY => $this->planCurrencyScroll($tierRange),
            GemScrollType::ITEM => $this->planItemScroll($tierRange),
        };
    }

    /**
     * Persist one runtime Gem Scroll Item from an already-rolled plan.
     *
     * @param GemScrollRollPlan $plan
     * @return Item
     */
    public function createFromPlan(GemScrollRollPlan $plan): Item
    {
        return match ($plan->scrollType()) {
            GemScrollType::XP => $this->createXpScroll($plan),
            GemScrollType::CURRENCY => $this->createCurrencyScroll($plan),
            GemScrollType::ITEM => $this->createItemScroll($plan),
        };
    }

    /**
     * Randomly choose one Gem Scroll family with equal probability.
     *
     * @return GemScrollType
     */
    private function rollScrollType(): GemScrollType
    {
        return match ($this->randomNumberGenerator->numberBetween(1, 3)) {
            1 => GemScrollType::XP,
            2 => GemScrollType::CURRENCY,
            default => GemScrollType::ITEM,
        };
    }

    /**
     * Randomly choose one target currency with equal probability.
     *
     * @return GemScrollCurrencyType
     */
    private function rollCurrencyType(): GemScrollCurrencyType
    {
        return match ($this->randomNumberGenerator->numberBetween(1, 4)) {
            1 => GemScrollCurrencyType::GOLD,
            2 => GemScrollCurrencyType::COPPER_COINS,
            3 => GemScrollCurrencyType::GOLD_DUST,
            default => GemScrollCurrencyType::SHARDS,
        };
    }

    /**
     * Roll a random ratio within the given inclusive range at four decimal precision.
     *
     * @param float $minimum
     * @param float $maximum
     * @return float
     */
    private function rollRatioInRange(float $minimum, float $maximum): float
    {
        if ($minimum >= $maximum) {
            return $minimum;
        }

        $minimumBasisPoints = intval(round($minimum * 10000));
        $maximumBasisPoints = intval(round($maximum * 10000));

        return $this->randomNumberGenerator->numberBetween($minimumBasisPoints, $maximumBasisPoints) / 10000.0;
    }

    /**
     * Roll the plan for an XP Gem Scroll for the given tier range.
     *
     * @param GemScrollTierRange $tierRange
     * @return GemScrollRollPlan
     */
    private function planXpScroll(GemScrollTierRange $tierRange): GemScrollRollPlan
    {
        return new GemScrollRollPlan(
            dropped: true,
            scrollType: GemScrollType::XP,
            bonus: $this->rollRatioInRange($tierRange->xpBonusMin(), $tierRange->xpBonusMax()),
            durationMinutes: $tierRange->durationMinutes(),
        );
    }

    /**
     * Roll the plan for a Currency Gem Scroll for the given tier range,
     * randomly choosing its target currency with equal probability.
     *
     * @param GemScrollTierRange $tierRange
     * @return GemScrollRollPlan
     */
    private function planCurrencyScroll(GemScrollTierRange $tierRange): GemScrollRollPlan
    {
        return new GemScrollRollPlan(
            dropped: true,
            scrollType: GemScrollType::CURRENCY,
            currencyType: $this->rollCurrencyType(),
            bonus: $this->rollRatioInRange($tierRange->currencyBonusMin(), $tierRange->currencyBonusMax()),
            durationMinutes: $tierRange->durationMinutes(),
        );
    }

    /**
     * Roll the plan for an Item Gem Scroll for the given tier range,
     * including its socket/pre-gemmed secondary chances.
     *
     * @param GemScrollTierRange $tierRange
     * @return GemScrollRollPlan
     */
    private function planItemScroll(GemScrollTierRange $tierRange): GemScrollRollPlan
    {
        return new GemScrollRollPlan(
            dropped: true,
            scrollType: GemScrollType::ITEM,
            bonus: $this->rollRatioInRange($tierRange->itemBonusMin(), $tierRange->itemBonusMax()),
            durationMinutes: $tierRange->durationMinutes(),
            socketChance: $this->rollRatioInRange($tierRange->socketChanceMin(), $tierRange->socketChanceMax()),
            preGemChance: $this->rollRatioInRange($tierRange->preGemChanceMin(), $tierRange->preGemChanceMax()),
        );
    }

    /**
     * Persist a runtime XP Gem Scroll Item from an already-rolled plan.
     *
     * @param GemScrollRollPlan $plan
     * @return Item
     */
    private function createXpScroll(GemScrollRollPlan $plan): Item
    {
        return Item::create([
            'name' => 'Gem Experience Scroll',
            'type' => 'alchemy',
            'randomly_generated' => true,
            'usable' => true,
            'can_craft' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_stack' => false,
            'lasts_for' => $plan->durationMinutes(),
            'gem_scroll_type' => GemScrollType::XP,
            'gem_scroll_bonus' => $plan->bonus(),
        ]);
    }

    /**
     * Persist a runtime Currency Gem Scroll Item from an already-rolled plan.
     *
     * @param GemScrollRollPlan $plan
     * @return Item
     */
    private function createCurrencyScroll(GemScrollRollPlan $plan): Item
    {
        return Item::create([
            'name' => $this->currencyScrollName($plan->currencyType()),
            'type' => 'alchemy',
            'randomly_generated' => true,
            'usable' => true,
            'can_craft' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_stack' => false,
            'lasts_for' => $plan->durationMinutes(),
            'gem_scroll_type' => GemScrollType::CURRENCY,
            'gem_scroll_bonus' => $plan->bonus(),
            'gem_scroll_currency_type' => $plan->currencyType(),
        ]);
    }

    /**
     * Persist a runtime Item Gem Scroll Item from an already-rolled plan,
     * including its socket/pre-gemmed secondary chances.
     *
     * @param GemScrollRollPlan $plan
     * @return Item
     */
    private function createItemScroll(GemScrollRollPlan $plan): Item
    {
        return Item::create([
            'name' => 'Gem Item Scroll',
            'type' => 'alchemy',
            'randomly_generated' => true,
            'usable' => true,
            'can_craft' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_stack' => false,
            'lasts_for' => $plan->durationMinutes(),
            'gem_scroll_type' => GemScrollType::ITEM,
            'gem_scroll_bonus' => $plan->bonus(),
            'gem_scroll_socket_chance' => $plan->socketChance(),
            'gem_scroll_pre_gem_chance' => $plan->preGemChance(),
        ]);
    }

    /**
     * Resolve the factual Currency Gem Scroll name for the given currency.
     *
     * @param GemScrollCurrencyType $currencyType
     * @return string
     */
    private function currencyScrollName(GemScrollCurrencyType $currencyType): string
    {
        return match ($currencyType) {
            GemScrollCurrencyType::GOLD => 'Gold Gem Scroll',
            GemScrollCurrencyType::COPPER_COINS => 'Copper Coins Gem Scroll',
            GemScrollCurrencyType::GOLD_DUST => 'Gold Dust Gem Scroll',
            GemScrollCurrencyType::SHARDS => 'Shards Gem Scroll',
        };
    }
}
