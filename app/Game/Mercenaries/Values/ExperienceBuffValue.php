<?php

namespace App\Game\Mercenaries\Values;

use Exception;
use Illuminate\Support\Collection;

class ExperienceBuffValue {

    const RANK_ONE   = 'Rank One';
    const RANK_TWO   = 'Rank Two';
    const RANK_THREE = 'Rank Three';
    const RANK_FOUR  = 'Rank Four';
    const RANK_FIVE  = 'Rank Five';
    const RANK_SIX   = 'Rank Six';
    const RANK_SEVEN = 'Rank Seven';

    const RANK_ONE_AMOUNT    = 0.10;
    const RANK_TWO_AMOUNT    = 0.50;
    const RANK_THREE_AMOUNT  = 0.75;
    const RANK_FOUR_AMOUNT   = 1.0;
    const RANK_FIVE_AMOUNT   = 1.50;
    const RANK_SIX_AMOUNT    = 1.75;
    const RANK_SEVEN_AMOUNT  = 2.25;

    const RANK_ONE_COST    = 500000000;
    const RANK_TWO_COST    = 750000000;
    const RANK_THREE_COST  = 1000000000;
    const RANK_FOUR_COST   = 10000000000;
    const RANK_FIVE_COST   = 100000000000;
    const RANK_SIX_COST    = 500000000000;
    const RANK_SEVEN_COST  = 1000000000000;

    protected static $values = [
        self::RANK_ONE    => self::RANK_ONE,
        self::RANK_TWO    => self::RANK_TWO,
        self::RANK_THREE  => self::RANK_THREE,
        self::RANK_FOUR   => self::RANK_FOUR,
        self::RANK_FIVE   => self::RANK_FIVE,
        self::RANK_SIX    => self::RANK_SIX,
        self::RANK_SEVEN  => self::RANK_SEVEN,
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
    public static function buffSelection(): array {
        $selection = [];

        $xpAmount = 0.0;
        $cost = 0;

        foreach (self::$values as $value) {

            switch($value) {
                case self::RANK_ONE:
                    $xpAmount = self::RANK_ONE_AMOUNT;
                    $cost     = self::RANK_ONE_COST;
                    break;
                case self::RANK_TWO:
                    $xpAmount = self::RANK_TWO_AMOUNT;
                    $cost     = self::RANK_TWO_COST;
                    break;
                case self::RANK_THREE:
                    $xpAmount = self::RANK_THREE_AMOUNT;
                    $cost     = self::RANK_THREE_COST;
                    break;
                case self::RANK_FOUR:
                    $xpAmount = self::RANK_FOUR_AMOUNT;
                    $cost     = self::RANK_FOUR_COST;
                    break;
                case self::RANK_FIVE:
                    $xpAmount = self::RANK_FIVE_AMOUNT;
                    $cost     = self::RANK_FIVE_COST;
                    break;
                case self::RANK_SIX:
                    $xpAmount = self::RANK_SIX_AMOUNT;
                    $cost     = self::RANK_SIX_COST;
                    break;
                case self::RANK_SEVEN:
                    $xpAmount = self::RANK_SEVEN_AMOUNT;
                    $cost     = self::RANK_SEVEN_COST;
                    break;
                default:
                    $xpAmount = 0.0;
            }

            $selection[] = [
                'label'     => $value,
                'value'     => $value,
                'xp_amount' => $xpAmount,
                'cost'      => $cost,
            ];
        }

        return $selection;
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

    public function getCost() {
        $name = self::$values[$this->value];

        switch($name) {
            case self::RANK_ONE:
                return self::RANK_ONE_COST;
            case self::RANK_TWO:
               return self::RANK_TWO_COST;
            case self::RANK_THREE:
                return self::RANK_THREE_COST;
            case self::RANK_FOUR:
                return self::RANK_FOUR_COST;
            case self::RANK_FIVE:
                return self::RANK_FIVE_COST;
            case self::RANK_SIX:
                return self::RANK_SIX_COST;
            case self::RANK_SEVEN:
                return self::RANK_SEVEN_COST;
            default:
                throw new Exception('Invalid Rank: ' . $name . ' for Cost.');
        }
    }

    public function getXPBuff() {
        $name = self::$values[$this->value];

        switch($name) {
            case self::RANK_ONE:
                return self::RANK_ONE_AMOUNT;
            case self::RANK_TWO:
                return self::RANK_TWO_AMOUNT;
            case self::RANK_THREE:
                return self::RANK_THREE_AMOUNT;
            case self::RANK_FOUR:
                return self::RANK_FOUR_AMOUNT;
            case self::RANK_FIVE:
                return self::RANK_FIVE_AMOUNT;
            case self::RANK_SIX:
                return self::RANK_SIX_AMOUNT;
            case self::RANK_SEVEN:
                return self::RANK_SEVEN_AMOUNT;
            default:
                throw new Exception('Invalid Rank: ' . $name . ' for XP.');
        }
    }

}
