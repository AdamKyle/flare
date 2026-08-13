<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Maps\Calculations\DistanceCalculation;
use Illuminate\Database\Eloquent\Collection;

class PortService
{
    /**
     * @var DistanceCalculation
     */
    private $distanceCalculator;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(DistanceCalculation $distanceCalculation)
    {
        $this->distanceCalculator = $distanceCalculation;
    }

    public function getPortDetails(Character $character): ?array
    {
        $currentPort = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('is_port', true)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($currentPort)) {
            return null;
        }

        $portList = $this->fetchOtherPorts($character, $currentPort);

        return [
            'current_port' => [
                'id' => $currentPort->id,
                'name' => $currentPort->name,
                'x' => $currentPort->x,
                'y' => $currentPort->y,
            ],
            'port_list' => $portList->map(function (Location $portLocation) {
                return [
                    'id' => $portLocation->id,
                    'name' => $portLocation->name,
                    'x' => $portLocation->x,
                    'y' => $portLocation->y,
                    'distance' => $portLocation->distance,
                    'time' => $portLocation->time,
                    'cost' => $portLocation->cost,
                    'can_afford' => $portLocation->can_afford,
                ];
            })->values()->all(),
        ];
    }

    public function doesMatch(Character $character, Location $from, Location $to, int $timeOut, int $cost): bool
    {
        $ports = $this->fetchOtherPorts($character, $from);

        $foundPort = $ports->filter(function ($port) use ($to) {
            return $port->id === $to->id;
        })->first();

        if (is_null($foundPort)) {
            return false;
        }

        return $foundPort->time === $timeOut && $foundPort->cost === $cost;
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
