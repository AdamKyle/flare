<?php

namespace App\Game\Gems\Values;

use App\Game\Core\Combat\Values\ElementType;
use Exception;

class GemTypeValue
{
    const FIRE = 0;

    const ICE = 1;

    const WATER = 2;

    private int $value;

    private static array $values = [
        self::FIRE => self::FIRE,
        self::ICE => self::ICE,
        self::WATER => self::WATER,
    ];

    private static array $elementTypes = [
        self::FIRE => ElementType::FIRE,
        self::ICE => ElementType::ICE,
        self::WATER => ElementType::WATER,
    ];

    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Return the gem type names keyed by their integer constant.
     */
    public static function getNames(): array
    {
        return array_map(fn (ElementType $elementType): string => $elementType->value, self::$elementTypes);
    }

    /**
     * Return the name of the element that the given element name only does half damage against.
     */
    public static function getOppsiteForHalfDamage(string $name): string
    {
        return self::resolveElementType($name)->halfDamageOpposite()->value;
    }

    /**
     * Return the name of the element that the given element name does double damage against.
     */
    public static function getOppsiteForDoubleDamage(string $name): string
    {
        return self::resolveElementType($name)->doubleDamageOpposite()->value;
    }

    /**
     * Return the name of this gem type's element.
     */
    public function getNameOfAtonement(): string
    {
        return self::$elementTypes[$this->value]->value;
    }

    /**
     * Determine whether this gem type is Fire.
     */
    public function isFire(): bool
    {
        return $this->value === self::FIRE;
    }

    /**
     * Determine whether this gem type is Ice.
     */
    public function isIce(): bool
    {
        return $this->value === self::ICE;
    }

    /**
     * Determine whether this gem type is Water.
     */
    public function isWater(): bool
    {
        return $this->value === self::WATER;
    }

    /**
     * Resolve the given element name to its Core ElementType, case-insensitively.
     */
    private static function resolveElementType(string $name): ElementType
    {
        foreach (ElementType::cases() as $elementType) {
            if (strtolower($elementType->value) === strtolower($name)) {
                return $elementType;
            }
        }

        throw new Exception($name.' does not exist.');
    }
}
