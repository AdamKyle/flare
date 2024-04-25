<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Calculations\DistanceCalculation;

class ResourceTransferService {

    use ResponseBuilder;

    /**
     * @param DistanceCalculation $distanceCalculation
     */
    public function __construct(private readonly DistanceCalculation $distanceCalculation) {}

    /**
     * Fetch kingdoms you can transfer resources from.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return array
     */
    public function fetchKingdomsToTransferResourcesFrom(Character $character, Kingdom $kingdom): array {

        $kingdoms = $character->kingdoms()->where('id', '!=', $kingdom->id)->get();

        $kingdoms = $kingdoms->map(function($otherKingdom) use ($kingdom) {

            $pixelDistance = $this->distanceCalculation->calculatePixel($kingdom->x_position, $kingdom->y_position,
                $otherKingdom->x_position, $otherKingdom->y_position);

            $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

            return [
                'kingdom_name' => $otherKingdom->name,
                'kingdom_id' => $otherKingdom->id,
                'x_position' => $otherKingdom->x_position,
                'y_position' => $otherKingdom->y_position,
                'current_stone' => $otherKingdom->current_stone,
                'current_wood' => $otherKingdom->current_wood,
                'current_steel' => $otherKingdom->current_steel,
                'current_clay' => $otherKingdom->current_clay,
                'current_iron' => $otherKingdom->current_iron,
                'time_to_travel' => $timeToKingdom,
            ];
        })->toArray();

        return $this->successResult([
            'kingdoms' => $kingdoms,
        ]);
    }
}
