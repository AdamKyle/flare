<?php

namespace App\Game\Core\Currency\Services;

use App\Game\Core\Currency\Values\CurrencyType;

class CurrencyLimit
{
    const MAX_GOLD = 2000000000000;

    const MAX_GOLD_DUST = 1000000;

    const MAX_SHARDS = 1000000;

    const MAX_COPPER = 1000000;

    public function __construct(
        private readonly int $amount,
        private readonly CurrencyType $currencyType,
    ) {}

    /**
     * Are we not allowed to give this currency?
     */
    public function canNotGiveCurrency(): bool
    {
        if ($this->isGold()) {
            return $this->amount >= self::MAX_GOLD;
        }

        if ($this->isGoldDust()) {
            return $this->amount >= self::MAX_GOLD_DUST;
        }

        if ($this->isShards()) {
            return $this->amount >= self::MAX_SHARDS;
        }

        if ($this->isCopper()) {
            return $this->amount >= self::MAX_COPPER;
        }

        // @codeCoverageIgnore
        return true;
    }

    /**
     * Are we gold?
     */
    public function isGold(): bool
    {
        return $this->currencyType === CurrencyType::GOLD;
    }

    /**
     * Are we gold dust?
     */
    public function isGoldDust(): bool
    {
        return $this->currencyType === CurrencyType::GOLD_DUST;
    }

    /**
     * Are we shards?
     */
    public function isShards(): bool
    {
        return $this->currencyType === CurrencyType::SHARDS;
    }

    /**
     * Is Copper Coins?
     */
    public function isCopper(): bool
    {
        return $this->currencyType === CurrencyType::COPPER;
    }
}
