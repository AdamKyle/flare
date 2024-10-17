<?php

namespace App\Game\Kingdoms\Values;

enum KingdomResources: string
{
    case WOOD = 'wood';
    case CLAY = 'clay';
    case STONE = 'stone';
    case IRON = 'iron';
    case STEEL = 'steel';
    case POPULATION = 'population';

    public static function kingdomResources(): array
    {
        return array_map(fn($resource) => $resource->value, self::cases());
    }
}
