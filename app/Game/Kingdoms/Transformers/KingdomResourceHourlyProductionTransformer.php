<?php

namespace App\Game\Kingdoms\Transformers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use League\Fractal\TransformerAbstract;

class KingdomResourceHourlyProductionTransformer extends TransformerAbstract
{
    public function transform(Kingdom $kingdom): array
    {
        return [
            'stone' => $this->resourceProduction($kingdom, 'stone'),
            'clay' => $this->resourceProduction($kingdom, 'clay'),
            'wood' => $this->resourceProduction($kingdom, 'wood'),
            'iron' => $this->resourceProduction($kingdom, 'iron'),
            'population' => $this->populationProduction($kingdom),
        ];
    }

    private function resourceProduction(Kingdom $kingdom, string $resource): float
    {
        $building = $kingdom->buildings->where('gives_resources', true)->where('increase_in_'.$resource)->first();

        if (is_null($building)) {
            return 0;
        }

        if ($building->current_durability === 0 || $building->max_durability === 0) {
            return 0;
        }

        $increaseAmount = $building->{'increase_in_'.$resource};
        $percentage = $this->durabilityPercentage($building);

        if ($percentage < 1) {
            return $increaseAmount + $increaseAmount * $percentage;
        }

        return $increaseAmount;
    }

    private function populationProduction(Kingdom $kingdom): float
    {
        $building = $kingdom->buildings->where('is_farm', true)->first();

        if (is_null($building)) {
            return 0;
        }

        if ($building->current_durability === 0 || $building->max_durability === 0) {
            return 0;
        }

        $increaseAmount = $building->population_increase;
        $percentage = $this->durabilityPercentage($building);

        if ($percentage < 1) {
            return $increaseAmount * $percentage;
        }

        return $increaseAmount;
    }

    private function durabilityPercentage(KingdomBuilding $building): float
    {
        return $building->current_durability / $building->max_durability;
    }
}
