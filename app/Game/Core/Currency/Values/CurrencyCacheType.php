<?php

namespace App\Game\Core\Currency\Values;

use App\Game\Core\Currency\Services\CurrencyLimit;

enum CurrencyCacheType: string
{
    case GOLD = 'gold';
    case GOLD_DUST = 'gold_dust';
    case SHARDS = 'shards';
    case COPPER_COINS = 'copper_coins';
    case GOLD_BARS = 'gold_bars';

    /**
     * Return the player-facing name of the currency stored in the cache.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::GOLD => 'Gold',
            self::GOLD_DUST => 'Gold Dust',
            self::SHARDS => 'Celestial Shards',
            self::COPPER_COINS => 'Copper Coins',
            self::GOLD_BARS => 'Gold Bars',
        };
    }

    /**
     * Return the largest amount of this currency a single cache may hold.
     *
     * @return int
     */
    public function maxCacheAmount(): int
    {
        return match ($this) {
            self::GOLD => CurrencyLimit::MAX_GOLD,
            self::GOLD_DUST => CurrencyLimit::MAX_GOLD_DUST,
            self::SHARDS => CurrencyLimit::MAX_SHARDS,
            self::COPPER_COINS => CurrencyLimit::MAX_COPPER,
            self::GOLD_BARS => 2_000,
        };
    }
}
