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


    /**
     * @var string[] $values
     */
    protected static array $values = [
        self::WEAPON => self::WEAPON,
        self::STAVE  => self::STAVE,
        self::HAMMER => self::HAMMER,
        self::BOW    => self::BOW,
    ];

    /**
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }
}
