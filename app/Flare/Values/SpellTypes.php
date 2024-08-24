<?php

namespace App\Flare\Values;

use Exception;

class SpellTypes
{
    private string $value;

    const HEALING = 'spell-healing';

    const DAMAGE = 'spell-damage';

    /**
     * @var string[]
     */
    protected static array $values = [
        self::HEALING => self::HEALING,
        self::DAMAGE => self::DAMAGE,
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

    public static function getTypes(): array
    {
        return self::$values;
    }

    /**
     * Is valid spell?
     */
    public static function isSpellType(string $type): bool
    {
        return in_array($type, self::$values);
    }
}
