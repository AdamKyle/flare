<?php

namespace App\Flare\Values;

use Exception;

class WeaponTypes {

    /**
     * @var string $value
     */
    private string $value;

    const WEAPON = 'weapon';
    CONST STAVE  = 'stave';
    const HAMMER = 'hammer';
    const BOW    = 'bow';
    const GUN    = 'gun';
    const FAN    = 'fan';
    const RING   = 'ring';


    /**
     * @var string[] $values
     */
    protected static array $values = [
        self::WEAPON => self::WEAPON,
        self::STAVE  => self::STAVE,
        self::HAMMER => self::HAMMER,
        self::BOW    => self::BOW,
        self::GUN    => self::GUN,
        self::FAN    => self::FAN,
        self::RING   => self::RING,
    ];

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
     * is Valid Weapon?
     *
     * @param string $type
     * @return bool
     */
    public static function isWeaponType(string $type): bool {
        return in_array($type, self::$values);
    }
}
