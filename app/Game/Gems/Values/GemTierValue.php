<?php

namespace App\Game\Gems\Values;

class GemTierValue {

    const TIER_ONE   = 1;
    const TIER_TWO   = 2;
    const TIER_THREE = 3;
    const TIER_FOUR  = 4;

    private int $value;

    public static array $values = [
        self::TIER_ONE   => self::TIER_ONE,
        self::TIER_TWO   => self::TIER_TWO,
        self::TIER_THREE => self::TIER_THREE,
        self::TIER_FOUR  => self::TIER_FOUR,
    ];

    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function isTierOne(): bool {
        return self::TIER_ONE;
    }

    public function isTierTwo(): bool {
        return self::TIER_TWO;
    }

    public function isTierThree(): bool {
        return self::TIER_THREE;
    }

    public function isTierFour(): bool {
        return self::TIER_THREE;
    }

    public function maxTierOneAmount(): array|null {
        if ($this->isTierOne()) {
            return [
                'min'       => 1,
                'max'       => 25,
                'min_level' => 1,
                'max_level' => 25,
                'cost' => [
                    'gold_dust'    => 1000,
                    'shards'       => 250,
                    'copper_coins' => 100,
                ],
                'chance'    => .30
            ];
        }

        return null;
    }

    public function maxTierTwoAmount(): array|null {
        if ($this->isTierTwo()) {
            return [
                'min'       => 5,
                'max'       => 50,
                'min_level' => 25,
                'max_level' => 50,
                'cost' => [
                    'gold_dust'    => 5000,
                    'shards'       => 500,
                    'copper_coins' => 250,
                ],
                'chance'    => .15
            ];
        }

        return null;
    }

    public function maxTierThreeAmount(): array|null {
        if ($this->isTierThree()) {
            return [
                'min'       => 10,
                'max'       => 75,
                'min_level' => 50,
                'max_level' => 75,
                'cost' => [
                    'gold_dust'    => 10000,
                    'shards'       => 1000,
                    'copper_coins' => 500,
                ],
                'chance'     => .05
            ];
        }

        return null;
    }

    public function maxTierFourAmount(): array|null {
        if ($this->isTierFour()) {
            return [
                'min'       => 20,
                'max'       => 100,
                'min_level' => 75,
                'max_level' => 100,
                'cost' => [
                    'gold_dust'    => 50000,
                    'shards'       => 2500,
                    'copper_coins' => 1000,
                ],
                'chance'    => 0.01
            ];
        }

        return null;
    }

    public function maxForTier(): array|null {
        return match ($this->value) {
            self::TIER_ONE   => $this->maxTierOneAmount(),
            self::TIER_TWO   => $this->maxTierTwoAmount(),
            self::TIER_THREE => $this->maxTierThreeAmount(),
            self::TIER_FOUR  => $this->maxTierFourAmount(),
            default          => null,
        };
    }
}
