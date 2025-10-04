<?php

namespace App\Flare\Items\Values;

enum EquippablePositionType: string
{
    case LEFT_HAND = 'left-hand';
    case RIGHT_HAND = 'right-hand';
    case SPELL_ONE = 'spell-one';
    case SPELL_TWO = 'spell-two';
    case RING_ONE = 'ring-one';
    case RING_TWO = 'ring-two';

    case BODY = 'body';
    case LEGGINGS = 'leggings';
    case SLEEVES = 'sleeves';
    case GLOVES = 'gloves';
    case FEET = 'feet';
    case HELMET = 'helmet';

    public static function orderForType(ItemType $type): array
    {
        return match ($type) {
            ItemType::SPELL_HEALING, ItemType::SPELL_DAMAGE => [self::SPELL_ONE, self::SPELL_TWO],
            ItemType::RING => [self::RING_ONE, self::RING_TWO],
            default => [self::LEFT_HAND, self::RIGHT_HAND],
        };
    }

    public static function values(array $slots): array
    {
        return array_map(fn (self $s) => $s->value, $slots);
    }
}
