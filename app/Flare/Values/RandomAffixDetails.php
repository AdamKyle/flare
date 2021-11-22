<?php

namespace App\Flare\Values;

class RandomAffixDetails {

    /**
     * @var string $value
     */
    private $value;

    const BASIC     = 1000000000;
    const MEDIUM    = 10000000000;
    const LEGENDARY = 50000000000;


    /**
     * @var int[] $values
     */
    protected static $values = [
        0 => self::BASIC,
        1 => self::MEDIUM,
        3 => self::LEGENDARY,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(int $value)
    {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function getPercentageRange(): array {
        switch($this->value) {
            case self::MEDIUM:
                return [150, 300];
            case self::LEGENDARY:
                return [325, 700];
            case self::BASIC:
            default:
                return [75, 125];
        }
    }

    public function getDamageRange(): array {
        switch($this->value) {
            case self::MEDIUM:
                return [10000000, 25000000];
            case self::LEGENDARY:
                return [50000000, 125000000];
            case self::BASIC:
            default:
                return [550000, 5000000];
        }
    }

    public function paidOneBillion(): bool {
        return $this->value === self::BASIC;
    }

    public function paidTenBillion(): bool {
        return $this->value === self::MEDIUM;
    }

    public function paidFiftyBillion(): bool {
        return $this->value === self::LEGENDARY;
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
            'Guardians Fear',
        ];
    }

}
