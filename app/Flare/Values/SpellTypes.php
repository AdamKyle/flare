<?php

namespace App\Flare\Values;

use Exception;

class SpellTypes {

    /**
     * @var string $value
     */
    private string $value;

    const HEALING  = 'spell-healing';
    const DAMAGE   = 'spell-damage';


    /**
     * @var string[] $values
     */
    protected static array $values = [
        self::HEALING => self::HEALING,
        self::DAMAGE  => self::DAMAGE,
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

    public static function getTypes(): array {
        return self::$values;
    }

    /**
     * Is valid spell?
     *
     * @param string $type
     * @return bool
     */
    public static function isSpellType(string $type): bool {
        return in_array($type, self::$values);
    }
}
