<?php

namespace App\Flare\Values;

use Exception;

class MaxCurrenciesValue
{
    const MAX_GOLD = 2000000000000;

    const MAX_GOLD_DUST = 1000000;

    const MAX_SHARDS = 1000000;

    const MAX_COPPER = 1000000;

    const GOLD = 0;

    const GOLD_DUST = 1;

    const SHARDS = 2;

    const COPPER = 3;

    private $value;

    private $amount;

    /**
     * @var string[]
     */
    protected static $values = [
        self::GOLD => 0,
        self::GOLD_DUST => 1,
        self::SHARDS => 2,
        self::COPPER => 3,
    ];

    /**
     * MaxLevel constructor.
     *
     * @throws Exception
     */
    public function __construct(int $amount, int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
        $this->amount = $amount;
    }

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
        return $this->value === self::GOLD;
    }

    /**
     * Are we gold dust?
     */
    public function isGoldDust(): bool
    {
        return $this->value === self::GOLD_DUST;
    }

    /**
     * Are we shards?
     */
    public function isShards(): bool
    {
        return $this->value === self::SHARDS;
    }

    /**
     * Is Copper Coins?
     */
    public function isCopper(): bool
    {
        return $this->value === self::COPPER;
    }
}
