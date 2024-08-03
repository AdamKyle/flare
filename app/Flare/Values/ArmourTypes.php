<?php

namespace App\Flare\Values;

use Exception;

class ArmourTypes
{
    private string $value;

    const SLEEVES = 'sleeves';

    const LEGGINGS = 'leggings';

    const GLOVES = 'gloves';

    const SHIELD = 'shield';

    const BODY = 'body';

    const FEET = 'feet';

    const HELMET = 'helmet';

    /**
     * @var string[]
     */
    protected static array $values = [
        self::SLEEVES => self::SLEEVES,
        self::LEGGINGS => self::LEGGINGS,
        self::GLOVES => self::GLOVES,
        self::SHIELD => self::SHIELD,
        self::BODY => self::BODY,
        self::FEET => self::FEET,
        self::HELMET => self::HELMET,
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

    public static function armourTypes(): array
    {
        return [
            self::SLEEVES,
            self::LEGGINGS,
            self::GLOVES,
            self::SHIELD,
            self::BODY,
            self::FEET,
            self::HELMET,
        ];
    }

    /**
     * Is valid armour?
     */
    public static function isArmourType(string $type): bool
    {
        return in_array($type, self::$values);
    }
}
