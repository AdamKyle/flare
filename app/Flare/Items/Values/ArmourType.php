<?php

namespace App\Flare\Items\Values;

enum ArmourType: string
{
    case SHIELD = 'shield';
    case BODY = 'body';
    case LEGGINGS = 'leggings';
    case SLEEVES = 'sleeves';
    case GLOVES = 'gloves';
    case FEET = 'feet';
    case HELMET = 'helmet';

    public static function getArmourPositions(): array
    {
        return [
            self::SHIELD->value => ['left-hand', 'right-hand'],
            self::BODY->value => ['body'],
            self::LEGGINGS->value => ['leggings'],
            self::SLEEVES->value => ['sleeves'],
            self::GLOVES->value => ['gloves'],
            self::FEET->value => ['feet'],
            self::HELMET->value => ['helmet'],
        ];
    }

    public static function allTypes(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
