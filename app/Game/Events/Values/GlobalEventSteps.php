<?php

namespace App\Game\Events\Values;

use Exception;

class GlobalEventSteps
{
    const BATTLE = 'fighting';

    const CRAFT = 'crafting';

    const ENCHANT = 'enchanting';

    private string $value;

    /**
     * @var int[]
     */
    protected static array $values = [
        self::BATTLE => self::BATTLE,
        self::CRAFT => self::CRAFT,
        self::ENCHANT => self::ENCHANT,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    public function isBattle(): bool
    {
        return $this->value === self::BATTLE;
    }

    public function isCrafting(): bool
    {
        return $this->value === self::CRAFT;
    }

    public function isEnchanting(): bool
    {
        return $this->value === self::ENCHANT;
    }
}
