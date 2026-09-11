<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemScroll;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Item;
use App\Game\Gems\Progression\Values\GemScrollAggregate;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use App\Game\Gems\Progression\Values\GemScrollType;
use Illuminate\Database\Eloquent\Collection;

class GemScrollEffectService
{
    /**
     * Resolve the active Gem Scroll aggregate for the Character's current
     * Map Gem profile. Pass `$forUpdate` inside a transaction to lock the
     * active Scroll rows while recomputing the cap for a new activation.
     *
     * @param Character $character
     * @param GameMapGemParamter $profile
     * @param bool $forUpdate
     * @return GemScrollAggregate
     */
    public function aggregateForMapProfile(Character $character, GameMapGemParamter $profile, bool $forUpdate = false): GemScrollAggregate
    {
        $query = CharacterGameMapGemScroll::query()
            ->where('character_id', $character->id)
            ->where('game_map_gem_paramter_id', $profile->id)
            ->active()
            ->with('item');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->buildAggregate($query->get());
    }

    /**
     * Resolve the active Gem Scroll aggregate for the Character's current
     * Location Gem profile. Pass `$forUpdate` inside a transaction to lock
     * the active Scroll rows while recomputing the cap for a new activation.
     *
     * @param Character $character
     * @param GameLocationGemParamter $profile
     * @param bool $forUpdate
     * @return GemScrollAggregate
     */
    public function aggregateForLocationProfile(Character $character, GameLocationGemParamter $profile, bool $forUpdate = false): GemScrollAggregate
    {
        $query = CharacterGameLocationGemScroll::query()
            ->where('character_id', $character->id)
            ->where('game_location_gem_paramter_id', $profile->id)
            ->active()
            ->with('item');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->buildAggregate($query->get());
    }

    /**
     * Build the typed aggregate from an already-loaded active Scroll collection.
     *
     * @param Collection $scrolls
     * @return GemScrollAggregate
     */
    private function buildAggregate(Collection $scrolls): GemScrollAggregate
    {
        if ($scrolls->isEmpty()) {
            return GemScrollAggregate::none();
        }

        $totals = [
            'primary' => 0.0,
            'xp' => 0.0,
            'item' => 0.0,
            'item_socket_chance' => 0.0,
            'item_pre_gem_chance' => 0.0,
            'active_count' => 0,
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
            $totals['primary'] += $bonus;
            $totals['active_count']++;

            match ($item->gem_scroll_type) {
                GemScrollType::XP => $this->accumulateXpScroll($totals, $bonus),
                GemScrollType::CURRENCY => $this->accumulateCurrencyScroll($totals, $item, $bonus),
                GemScrollType::ITEM => $this->accumulateItemScroll($totals, $item, $bonus),
            };
        }

        return new GemScrollAggregate(
            $totals['primary'],
            $totals['xp'],
            $totals[GemScrollCurrencyType::GOLD->value],
            $totals[GemScrollCurrencyType::COPPER_COINS->value],
            $totals[GemScrollCurrencyType::GOLD_DUST->value],
            $totals[GemScrollCurrencyType::SHARDS->value],
            $totals['item'],
            min(1.0, $totals['item_socket_chance']),
            min(1.0, $totals['item_pre_gem_chance']),
            $totals['active_count'],
        );
    }

    /**
     * Accumulate one active XP Gem Scroll's primary bonus into the running totals.
     *
     * @param array $totals
     * @param float $bonus
     */
    private function accumulateXpScroll(array &$totals, float $bonus): void
    {
        $totals['xp'] += $bonus;
    }

    /**
     * Accumulate one active Currency Gem Scroll's primary bonus into the running totals.
     *
     * @param array $totals
     * @param Item $item
     * @param float $bonus
     */
    private function accumulateCurrencyScroll(array &$totals, Item $item, float $bonus): void
    {
        if (is_null($item->gem_scroll_currency_type)) {
            return;
        }

        $totals[$item->gem_scroll_currency_type->value] += $bonus;
    }

    /**
     * Accumulate one active Item Gem Scroll's primary bonus and secondary chances into the running totals.
     *
     * @param array $totals
     * @param Item $item
     * @param float $bonus
     */
    private function accumulateItemScroll(array &$totals, Item $item, float $bonus): void
    {
        $totals['item'] += $bonus;
        $totals['item_socket_chance'] += $item->gem_scroll_socket_chance ?? 0.0;
        $totals['item_pre_gem_chance'] += $item->gem_scroll_pre_gem_chance ?? 0.0;
    }
}
