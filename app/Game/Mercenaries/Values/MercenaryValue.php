<?php

namespace App\Game\Mercenaries\Values;

use Exception;
use Illuminate\Support\Collection;

class MercenaryValue {

    const CHILD_OF_GOLD_DUST    = 'child-of-gold-dust';
    const CHILD_OF_SHARDS       = 'child-of-shards';
    const CHILD_OF_COPPER_COINS = 'child-of-copper-coins';
    const CHILD_OF_GAMBLING     = 'child-of-gambling';

    const MERCENARY_COST        = 10000000;
    const XP_REQUIRED           = 1000;
    const XP_PER_KILL           = 25;
    const MAX_LEVEL             = 100;
    const MAX_REINCARNATION     = 10;
    const REINCARNATION_COST    = 500;

    protected static $values = [
        self::CHILD_OF_GOLD_DUST    => 'child-of-gold-dust',
        self::CHILD_OF_SHARDS       => 'child-of-shards',
        self::CHILD_OF_COPPER_COINS => 'child-of-copper-coins',
        self::CHILD_OF_GAMBLING     => 'child-of-gambling'
    ];

    /**
     * @var string $value
     */
    private string $value;

    /**
     * List of mercenaries.
     *
     * @return string[]
     */
    public static function mercenaries(Collection $characterMercenaries): array {
        $mercenaries = [];

        foreach (self::$values as $merc) {
            if ($characterMercenaries->where('mercenary_type', $merc)->isNotEmpty()) {
                continue;
            }

            $mercenaries[] = [
                'name'  => ucfirst(str_replace('-', ' ', $merc)),
                'value' => $merc,
            ];
        }

        return $mercenaries;
    }

    /**
     * @param string $value
     * @throws Exception
     */
    public function __construct(string $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Get the name of the mercenary.
     *
     * @return string
     */
    public function getName(): string {
        return ucfirst(str_replace('-', ' ', $this->value));
    }

    /**
     * Is at max level?
     *
     * @param int $currentLevel
     * @return bool
     */
    public function isAtMaxLevel(int $currentLevel): bool {
        return $currentLevel === self::MAX_LEVEL;
    }

    /**
     * Get the cost of the mercenary.
     *
     * @return int
     */
    public function getCost(): int {
        return self::MERCENARY_COST;
    }

    public function getMaxBonus(float $reincarnatedBonus = 0): float {
        return 1 + $reincarnatedBonus;
    }

    /**
     * Get Next XP Required.
     *
     * @param float|null $xpIncrease
     * @return int
     */
    public function getNextLevelXP(?float $xpIncrease = null): int {

        if (is_null($xpIncrease)) {
            return self::XP_REQUIRED;
        }

        return self::XP_REQUIRED + self::XP_REQUIRED * $xpIncrease;
    }

    /**
     * Get bonus for mercenary based on the current level.
     *
     * @param int $currentLevel
     * @param float $reincarnatedBonus
     * @return int|float
     */
    public function getBonus(int $currentLevel, float $reincarnatedBonus = 0): int|float {
        return (0.01 * $currentLevel) + $reincarnatedBonus;
    }
}
