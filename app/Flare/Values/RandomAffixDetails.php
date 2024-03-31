<?php

namespace App\Flare\Values;

class RandomAffixDetails {

    /**
     * @var string $value
     */
    private $value;

    const BASIC     = 2000000000;
    const MEDIUM    = 4000000000;
    const LEGENDARY = 100000000000;
    const MYTHIC    = 500000000000;


    /**
     * @var int[] $values
     */
    protected static $values = [
        0 => self::BASIC,
        1 => self::MEDIUM,
        3 => self::LEGENDARY,
        4 => self::MYTHIC,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public static function isMythicForCost(int $amountPaid) {
        return self::MYTHIC === $amountPaid;
    }

    public function getPercentageRange(): array {
        switch ($this->value) {
            case self::MEDIUM:
                return [50, 150];
            case self::LEGENDARY:
                return [175, 300];
            case self::MYTHIC:
                return [400, 800];
            case self::BASIC:
            default:
                return [5, 25];
        }
    }

    public function getDamageRange(): array {
        switch ($this->value) {
            case self::MEDIUM:
                return [35, 85];
            case self::LEGENDARY:
                return [50, 150];
            case self::MYTHIC:
                return [150, 300];
            case self::BASIC:
            default:
                return [25, 75];
        }
    }

    public function paidTenBillion(): bool {
        return $this->value === self::BASIC;
    }

    public function paidFiftyBillion(): bool {
        return $this->value === self::MEDIUM;
    }

    public function paidHundredBillion(): bool {
        return $this->value === self::LEGENDARY;
    }

    public function paidFiveHundredBillion(): bool {
        return $this->value === self::MYTHIC;
    }

    public static function names(): array {
        return [
            'Petrifying Hatred of Disease',
            'Almighty Rage of the Dead',
            'Sanctifying Earth Tendrils',
            'Serenity of Life',
            'Rebirth of the Ancients',
            'Enchantment of Apathy',
            'Decay and Festering',
            'Scream of Sanctification',
            'Invincibility Rod',
            'Spiritbound Rage',
            'Exile\'s Enchantment',
            'Demonic Infinity',
            'Guardian\'s Fear',
            'Esoteric Flash',
            'Tranquility Gift',
            'Putrefaction of Mortality',
            'Beam of Lightning',
            'Burst of Precision'
        ];
    }
}
