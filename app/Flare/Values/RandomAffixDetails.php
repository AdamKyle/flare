<?php

namespace App\Flare\Values;

use Exception;

class RandomAffixDetails
{
    /**
     * @var string
     */
    private $value;

    const BASIC = 2000000000;

    const MEDIUM = 4000000000;

    const LEGENDARY = 80000000000;

    const MYTHIC = 500000000000;

    const COSMIC = 1000000000000;

    /**
     * @var int[]
     */
    protected static $values = [
        0 => self::BASIC,
        1 => self::MEDIUM,
        3 => self::LEGENDARY,
        4 => self::MYTHIC,
        5 => self::COSMIC,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    public function getPercentageRange(): array
    {
        switch ($this->value) {
            case self::MEDIUM:
                return [50, 150];
            case self::LEGENDARY:
                return [175, 300];
            case self::MYTHIC:
                return [400, 800];
            case self::COSMIC:
                return [900, 1200];
            case self::BASIC:
            default:
                return [5, 25];
        }
    }

    public function getDamageRange(): array
    {
        switch ($this->value) {
            case self::MEDIUM:
                return [35, 85];
            case self::LEGENDARY:
                return [50, 150];
            case self::MYTHIC:
                return [150, 300];
            case self::COSMIC:
                return [300, 500];
            case self::BASIC:
            default:
                return [25, 75];
        }
    }

    public static function names(): array
    {
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
            'Burst of Precision',
        ];
    }
}
