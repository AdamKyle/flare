<?php

namespace App\Game\Gambler\Values;

class CurrencyValue {

    const GOLD_DUST    = 1;
    const SHARDS       = 2;
    const COPPER_COINS = 3;

    protected static $values = [
        self::GOLD_DUST    => 1,
        self::SHARDS       => 2,
        self::COPPER_COINS => 3,
    ];

    protected static $attributes = [
        self::GOLD_DUST    => 'gold_dust',
        self::SHARDS       => 'shards',
        self::COPPER_COINS => 'copper_coins',
    ];

    protected static $rollIcons = [
        [
            'icon'  => 'ra ra-crystals',
            'type'  => 1,
            'color' => '#c96124',
            'title' => 'Gold Dust'
        ],
        [
            'icon'  => 'ra ra-ankh',
            'type'  => 2,
            'color' => '#b632ba',
            'title' => 'Shards'
        ],
        [
            'icon'  => 'fa fa-coins',
            'type'  => 3,
            'color' => '#b07e12',
            'title' => 'Copper Coins'
        ],
        [
            'icon'  => 'fa fa-apple-alt',
            'type'  => 4,
            'color' => '#23c240',
            'title' => 'Apple'
        ],
        [
            'icon'  => 'fas fa-seedling',
            'type'  => 5,
            'color' => '#23c240',
            'title' => 'Seedling'
        ],
        [
            'icon'  => 'fas fa-carrot',
            'type'  => 7,
            'color' => '#2350c2',
            'title' => 'Carrot',
        ],
    ];

    private int $value;

    /**
     * @param int $value
     * @throws \Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function getAttribute(): string {
        return self::$attributes[$this->value];
    }

    public static function getIcons(): array {
        return self::$rollIcons;
    }

    public static function roll(): array {

        $rollOne   = rand(0, count(self::$rollIcons) - 1);
        $rollTwo   = rand(0, count(self::$rollIcons) - 1);
        $rollThree = rand(0, count(self::$rollIcons) - 1);

        $rolls      = [$rollOne, $rollTwo, $rollThree];

        $difference = array_diff_assoc($rolls, array_unique($rolls));
        $difference = array_values($difference);

        return self::processRoll($difference, $rolls);
    }

    protected static function processRoll(array $difference, array $rolls): array {
        $matching       = null;
        $matchingAmount = 0;


        if (count($difference) === 1) {
            $index      = $difference[0];
            $itemRolled = self::$rollIcons[$index]['type'];

            if (in_array($itemRolled, self::$values)) {
                $matchingAmount = 2;
                $matching       = $itemRolled;
            }
        }

        if (count($difference) === 2) {
            $index      = $difference[0];
            $itemRolled = self::$rollIcons[$index]['type'];

            if (in_array($itemRolled, self::$values)) {
                $matchingAmount = 3;
                $matching       = $itemRolled;
            }
        }

        return [
            'roll'           => $rolls,
            'matching'       => $matching,
            'matchingAmount' => $matchingAmount
        ];
    }
}
