<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Item;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use App\Game\Gems\Progression\Values\GemScrollTierRange;
use App\Game\Gems\Progression\Values\GemScrollTierRanges;
use App\Game\Gems\Progression\Values\GemScrollType;

/**
 * Randomly generates one runtime Gem Scroll `Item` row for a successful
 * Scroll reward roll, using the exact tier ranges owned by
 * `GemScrollTierRanges` for the Character's current personal Gem
 * progression level.
 */
class GemScrollGenerator
{
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly GemScrollTierRanges $gemScrollTierRanges,
    ) {}

    /**
     * Generate one Gem Scroll Item for the given personal Gem progression
     * level, randomly choosing its family with equal probability.
     */
    public function generateForPersonalLevel(int $personalLevel): Item
    {
        $tierRange = $this->gemScrollTierRanges->forPersonalLevel($personalLevel);

        return match ($this->rollScrollType()) {
            GemScrollType::XP => $this->generateXpScroll($tierRange),
            GemScrollType::CURRENCY => $this->generateCurrencyScroll($tierRange),
            GemScrollType::ITEM => $this->generateItemScroll($tierRange),
        };
    }

    /**
     * Randomly choose one Gem Scroll family with equal probability.
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
     * Generate a runtime XP Gem Scroll Item for the given tier range.
     */
    private function generateXpScroll(GemScrollTierRange $tierRange): Item
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
            'lasts_for' => $tierRange->durationMinutes(),
            'gem_scroll_type' => GemScrollType::XP,
            'gem_scroll_bonus' => $this->rollRatioInRange($tierRange->xpBonusMin(), $tierRange->xpBonusMax()),
        ]);
    }

    /**
     * Generate a runtime Currency Gem Scroll Item for the given tier range,
     * randomly choosing its target currency with equal probability.
     */
    private function generateCurrencyScroll(GemScrollTierRange $tierRange): Item
    {
        $currencyType = $this->rollCurrencyType();

        return Item::create([
            'name' => $this->currencyScrollName($currencyType),
            'type' => 'alchemy',
            'randomly_generated' => true,
            'usable' => true,
            'can_craft' => false,
            'market_sellable' => false,
            'can_drop' => false,
            'can_stack' => false,
            'lasts_for' => $tierRange->durationMinutes(),
            'gem_scroll_type' => GemScrollType::CURRENCY,
            'gem_scroll_bonus' => $this->rollRatioInRange($tierRange->currencyBonusMin(), $tierRange->currencyBonusMax()),
            'gem_scroll_currency_type' => $currencyType,
        ]);
    }

    /**
     * Generate a runtime Item Gem Scroll Item for the given tier range,
     * including its socket/pre-gemmed secondary chances.
     */
    private function generateItemScroll(GemScrollTierRange $tierRange): Item
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
            'lasts_for' => $tierRange->durationMinutes(),
            'gem_scroll_type' => GemScrollType::ITEM,
            'gem_scroll_bonus' => $this->rollRatioInRange($tierRange->itemBonusMin(), $tierRange->itemBonusMax()),
            'gem_scroll_socket_chance' => $this->rollRatioInRange($tierRange->socketChanceMin(), $tierRange->socketChanceMax()),
            'gem_scroll_pre_gem_chance' => $this->rollRatioInRange($tierRange->preGemChanceMin(), $tierRange->preGemChanceMax()),
        ]);
    }

    /**
     * Resolve the factual Currency Gem Scroll name for the given currency.
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
