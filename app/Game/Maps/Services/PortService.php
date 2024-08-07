<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Values\MapPositionValue;
use Illuminate\Database\Eloquent\Collection;

class PortService
{
    /**
     * @var DistanceCalculation
     */
    private $distanceCalculator;

    /**
     * @var MapPositionValue
     */
    private $mapPositionValue;

    /**
     * @var array
     */
    private $portDetails = [];

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(DistanceCalculation $distanceCalculation, MapPositionValue $mapPositionValue)
    {
        $this->distanceCalculator = $distanceCalculation;
        $this->mapPositionValue = $mapPositionValue;
    }

    /**
     * Get the port details
     */
    public function getPortDetails(Character $character, Location $location): array
    {

        $this->portDetails['current_port'] = $location->getAttributes();

        $this->portDetails['port_list'] = $this->fetchOtherPorts($character, $location);

        return $this->portDetails;
    }

    /**
     * Does the port match?
     *
     * First we need the other ports that don't match the current one.
     *
     * next we need to filter out the ports till we find the one that matches by id, based on
     * where you are going to.
     *
     * Next we return a boolean based on if the timeout and the cost matches
     * that of where you are going.
     */
    public function doesMatch(Character $character, Location $from, Location $to, int $timeOut, int $cost): bool
    {
        $ports = $this->fetchOtherPorts($character, $from);

        $foundPort = $ports->filter(function ($port) use ($to) {
            return $port->id === $to->id;
        })->first();

        return $foundPort->time === $timeOut && $foundPort->cost === $cost;
    }

    /**
     * Set sail
     */
    public function setSail(Character $character, Location $newPort): Character
    {
        $character->map()->update([
            'character_position_x' => $newPort->x,
            'character_position_y' => $newPort->y,
            'position_x' => $this->mapPositionValue->fetchXPosition($character->map->character_position_x, $character->map->position_x),
            'position_y' => $this->mapPositionValue->fetchYPosition($character->map->character_position_y),
        ]);

        return $character->refresh();
    }

    /**
     * Fetch other ports that you are not currently at.
     */
    protected function fetchOtherPorts(Character $character, Location $location): Collection
    {
        $locations = Location::where('id', '!=', $location->id)->where('is_port', true)->where('game_map_id', $character->map->game_map_id)->get();

        $locationData = $locations->transform(function ($portLocation) use ($character, $location) {
            $distance = $this->distanceCalculator->calculatePixel($portLocation->x, $portLocation->y, $location->x, $location->y);
            $time = $this->distanceCalculator->calculateMinutes($distance);
            $cost = ($time * 1000);

            $portLocation->distance = $distance;
            $portLocation->time = $time;
            $portLocation->cost = $cost;
            $portLocation->can_afford = $character->gold >= $cost ? true : false;

            return $portLocation;
        });

        return $locationData;
    }
}
