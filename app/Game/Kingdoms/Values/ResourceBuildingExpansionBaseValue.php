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


    public static function resourceCostsForExpansion(?KingdomBuildingExpansion $buildingExpansion = null): array {

        if (is_null($buildingExpansion)) {
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
            'stone' => self::BASE_RESOURCE_REQUIRED * $buildingExpansion->expansion_count,
            'wood'  => self::BASE_RESOURCE_REQUIRED * $buildingExpansion->expansion_count,
            'iron'  => self::BASE_RESOURCE_REQUIRED * $buildingExpansion->expansion_count,
            'clay'  => self::BASE_RESOURCE_REQUIRED * $buildingExpansion->expansion_count,
            'steel' => self::BASE_STEEL_REQUIRED * $buildingExpansion->expansion_count,
            'population' => self::POPULATION_COST * $buildingExpansion->expansion_count,
        ];
    }
}
