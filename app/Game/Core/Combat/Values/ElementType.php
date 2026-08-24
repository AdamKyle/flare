<?php

namespace App\Game\Core\Combat\Values;

enum ElementType: string
{
    case FIRE = 'Fire';
    case ICE = 'Ice';
    case WATER = 'Water';

    /**
     * Return the element that this element only does half damage against.
     */
    public function halfDamageOpposite(): ElementType
    {
        return match ($this) {
            self::FIRE => self::WATER,
            self::ICE => self::FIRE,
            self::WATER => self::ICE,
        };
    }

    /**
     * Return the element that this element does double damage against.
     */
    public function doubleDamageOpposite(): ElementType
    {
        return match ($this) {
            self::WATER => self::FIRE,
            self::FIRE => self::ICE,
            self::ICE => self::WATER,
        };
    }
}
