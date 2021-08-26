<?php

namespace App\Flare\Values;

class MaxCurrenciesValue {

    const MAX_GOLD      = 2000000000000;
    const MAX_GOLD_DUST = 2000000000;
    const MAX_SHARDS    = 2000000000;

    const GOLD      = 0;
    const GOLD_DUST = 1;
    const SHARDS    = 2;

    private $value;

    private $amount;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::GOLD => 0,
        self::GOLD_DUST => 1,
        Self::SHARDS => 2,
    ];

    /**
     * MaxLevel constructor.
     *
     * @param int $currentLevel
     * @param int $xp
     */
    public function __construct(int $amount, int $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value  = $value;
        $this->amount = $amount;
    }

    /**
     * Are we not allowed to give this currency?
     *
     * @return bool
     */
    public function canNotGiveCurrency(): bool {
        if ($this->isGold()) {
            return $this->amount >= self::MAX_GOLD;
        }

        if ($this->isGoldDust()) {
            return $this->amount >= self::MAX_GOLD_DUST;
        }

        if ($this->isShards()) {
            return $this->amount >= self::MAX_SHARDS;
        }

        // @codeCoverageIgnore
        return true;
    }

    /**
     * Are we gold?
     *
     * @return bool
     */
    public function isGold(): bool {
        return $this->value === self::GOLD;
    }

    /**
     * Are we gold dust?
     *
     * @return bool
     */
    public function isGoldDust(): bool {
        return $this->value === self::GOLD_DUST;
    }

    /**
     * Are we shards?
     *
     * @return bool
     */
    public function isShards(): bool {
        return $this->value === self::SHARDS;
    }
}
