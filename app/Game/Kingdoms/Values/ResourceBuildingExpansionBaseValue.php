<?php

namespace App\Game\Kingdoms\Values;

use App\Flare\Models\KingdomBuildingExpansion;

class ResourceBuildingExpansionBaseValue {

    const BASE_MINUTES_REQUIRED = 15;

    const BASE_RESOURCE_REQUIRED = 31000;

    const BASE_RESOURCE_GAIN = 31000;

    const BASE_STEEL_REQUIRED = 16000;

    const POPULATION_COST = 60;

    const BASE_GOLD_BARS_REQUIRED = 100;

    const MAX_EXPANSIONS = 8;


    public static function resourceCostsForExpansion(int $expansionCount = 0): array {

        if ($expansionCount === 0) {
            return [
                'stone' => self::BASE_RESOURCE_REQUIRED,
                'wood'  => self::BASE_RESOURCE_REQUIRED,
                'iron'  => self::BASE_RESOURCE_REQUIRED,
                'clay'  => self::BASE_RESOURCE_REQUIRED,
                'steel' => self::BASE_STEEL_REQUIRED,
                'population' => self::POPULATION_COST,
            ];
        }

        return [
            'stone' => self::BASE_RESOURCE_REQUIRED * $expansionCount,
            'wood'  => self::BASE_RESOURCE_REQUIRED * $expansionCount,
            'iron'  => self::BASE_RESOURCE_REQUIRED * $expansionCount,
            'clay'  => self::BASE_RESOURCE_REQUIRED * $expansionCount,
            'steel' => self::BASE_STEEL_REQUIRED * $expansionCount,
            'population' => self::POPULATION_COST * $expansionCount,
        ];
    }
}
