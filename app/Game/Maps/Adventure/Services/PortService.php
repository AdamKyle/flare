<?php

namespace App\Game\Maps\Adventure\Services;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Values\MapPositionValue;

class PortService {

    private $distanceCalculator;

    private $mapPositionValue;

    private $portDetails = [];

    public function __construct(DistanceCalculation $distanceCalculation, MapPositionValue $mapPositionValue) {
        $this->distanceCalculator = $distanceCalculation;
        $this->mapPositionValue   = $mapPositionValue;
    }

    public function getPortDetails(Character $character, Location $location): array {

        $this->portDetails['current_port'] = $location->getAttributes();

        $this->portDetails['port_list'] = $this->fetchOtherPorts($character, $location);

        return $this->portDetails;
    }

    public function setSail(Character $character, Location $newPort): Character {
        $character->map()->update([
            'character_position_x' => $newPort->x,
            'character_position_y' => $newPort->y,
            'position_x'           => $this->mapPositionValue->fetchXPosition($character->map->character_position_x, $character->map->position_x),
            'position_x'           => $this->mapPositionValue->fetchYPosition($character->map->character_position_y, $character->map->position_y),
        ]);

        return $character->refresh();
    }

    protected function fetchOtherPorts(Character $character, Location $location): Collection {
        $locations = Location::where('id', '!=', $location->id)->where('is_port', true)->get();

        $locationData = $locations->transform(function($portLocation) use($character, $location) {
            $distance = $this->distanceCalculator->calculatePixel($portLocation->x, $portLocation->y, $location->x, $location->y);
            $time     = $this->distanceCalculator->calculateMinutes($distance);
            $cost     = ($time * 100); 

            $portLocation->name       = $portLocation->name;
            $portLocation->distance   = $distance;
            $portLocation->time       = $time;
            $portLocation->cost       = $cost;
            $portLocation->can_afford = $character->gold >= $cost ? true : false;

            return $portLocation;
        });

        return $locationData;
    }
}