<?php

namespace App\Flare\Values;

use Exception;

class WeaponTypes
{
    private string $value;

    const WEAPON = 'weapon';

    const STAVE = 'stave';

    const HAMMER = 'hammer';

    const BOW = 'bow';

    const GUN = 'gun';

    const MACE = 'mace';

    const FAN = 'fan';

    const SCRATCH_AWL = 'scratch-awl';

    const RING = 'ring';

    /**
     * @var string[]
     */
    protected static array $values = [
        self::WEAPON => self::WEAPON,
        self::STAVE => self::STAVE,
        self::HAMMER => self::HAMMER,
        self::BOW => self::BOW,
        self::GUN => self::GUN,
        self::FAN => self::FAN,
        self::MACE => self::MACE,
        self::SCRATCH_AWL => self::SCRATCH_AWL,
        self::RING => self::RING,
    ];

    /**
     * @throws Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * is Valid Weapon?
     */
    public static function isWeaponType(string $type): bool
    {
        return in_array($type, self::$values);
    }
}
