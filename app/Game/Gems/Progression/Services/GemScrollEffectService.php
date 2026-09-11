<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemScroll;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Game\Gems\Progression\Values\GemScrollAggregate;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use App\Game\Gems\Progression\Values\GemScrollType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves the typed active-Gem-Scroll aggregate for one Character + exact
 * Map/Location Gem profile in a single query, so reward/mutation subservices
 * never repeatedly load and re-sum active Scroll rows.
 */
class GemScrollEffectService
{
    /**
     * Resolve the active Gem Scroll aggregate for the Character's current Map Gem profile.
     */
    public function aggregateForMapProfile(Character $character, GameMapGemParamter $profile): GemScrollAggregate
    {
        $scrolls = CharacterGameMapGemScroll::query()
            ->where('character_id', $character->id)
            ->where('game_map_gem_paramter_id', $profile->id)
            ->active()
            ->with('item')
            ->get();

        return $this->buildAggregate($scrolls);
    }

    /**
     * Resolve the active Gem Scroll aggregate for the Character's current Location Gem profile.
     */
    public function aggregateForLocationProfile(Character $character, GameLocationGemParamter $profile): GemScrollAggregate
    {
        $scrolls = CharacterGameLocationGemScroll::query()
            ->where('character_id', $character->id)
            ->where('game_location_gem_paramter_id', $profile->id)
            ->active()
            ->with('item')
            ->get();

        return $this->buildAggregate($scrolls);
    }

    /**
     * Build the typed aggregate from an already-loaded active Scroll collection.
     */
    private function buildAggregate(Collection $scrolls): GemScrollAggregate
    {
        if ($scrolls->isEmpty()) {
            return GemScrollAggregate::none();
        }

        $totalPrimaryBonus = 0.0;
        $xpBonusTotal = 0.0;
        $itemBonusTotal = 0.0;
        $itemSocketChance = 0.0;
        $itemPreGemChance = 0.0;
        $activeCount = 0;

        $currencyTotals = [
            GemScrollCurrencyType::GOLD->value => 0.0,
            GemScrollCurrencyType::COPPER_COINS->value => 0.0,
            GemScrollCurrencyType::GOLD_DUST->value => 0.0,
            GemScrollCurrencyType::SHARDS->value => 0.0,
        ];

        foreach ($scrolls as $scroll) {
            $item = $scroll->item;

            if (is_null($item) || is_null($item->gem_scroll_type)) {
                continue;
            }

            $bonus = $item->gem_scroll_bonus ?? 0.0;
            $totalPrimaryBonus += $bonus;
            $activeCount++;

            if ($item->gem_scroll_type === GemScrollType::XP) {
                $xpBonusTotal += $bonus;

                continue;
            }

            if ($item->gem_scroll_type === GemScrollType::CURRENCY) {
                if (! is_null($item->gem_scroll_currency_type)) {
                    $currencyTotals[$item->gem_scroll_currency_type->value] += $bonus;
                }

                continue;
            }

            $itemBonusTotal += $bonus;
            $itemSocketChance += $item->gem_scroll_socket_chance ?? 0.0;
            $itemPreGemChance += $item->gem_scroll_pre_gem_chance ?? 0.0;
        }

        return new GemScrollAggregate(
            $totalPrimaryBonus,
            $xpBonusTotal,
            $currencyTotals[GemScrollCurrencyType::GOLD->value],
            $currencyTotals[GemScrollCurrencyType::COPPER_COINS->value],
            $currencyTotals[GemScrollCurrencyType::GOLD_DUST->value],
            $currencyTotals[GemScrollCurrencyType::SHARDS->value],
            $itemBonusTotal,
            min(1.0, $itemSocketChance),
            min(1.0, $itemPreGemChance),
            $activeCount,
        );
    }
}
