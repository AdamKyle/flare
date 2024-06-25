<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomBuildingTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class CapitalCityManagementService
{
    use ResponseBuilder;

    public function __construct(
        private readonly UpdateKingdom $updateKingdom,
        private readonly CapitalCityBuildingManagement $capitalCityBuildingManagement,
        private readonly KingdomBuildingTransformer $kingdomBuildingTransformer,
        private readonly Manager $manager
    ) {}

    /**
     * Make the current kingdom a capital city.
     *
     * @param Kingdom $kingdom
     * @return array
     */
    public function makeCapitalCity(Kingdom $kingdom): array
    {
        $this->validateOneCapitalCityPerPlane($kingdom);

        $kingdom->update(['is_capital' => true]);
        $this->updateKingdom($kingdom);

        return $this->successResult([
            'message' => $this->getCapitalCityMessage($kingdom),
        ]);
    }

    /**
     * Fetch buildings from other kingdoms for upgrades or repairs.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param bool $returnArray
     * @return array
     */
    public function fetchBuildingsForUpgradesOrRepairs(Character $character, Kingdom $kingdom, bool $returnArray = false): array
    {
        $kingdoms = $this->getOtherKingdoms($character, $kingdom);
        $kingdomBuildingData = $this->fetchBuildingsData($kingdoms);

        return $returnArray ? $kingdomBuildingData : $this->successResult($kingdomBuildingData);
    }

    /**
     * Fetch kingdoms for selection.
     *
     * @param Kingdom $kingdom
     * @return array
     */
    public function fetchKingdomsForSelection(Kingdom $kingdom): array
    {
        $kingdoms = $this->getSelectableKingdoms($kingdom);
        return $this->successResult(['kingdoms' => $kingdoms]);
    }

    /**
     * Walk all kingdoms for the character.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return array
     */
    public function walkAllKingdoms(Character $character, Kingdom $kingdom): array
    {
        $this->updateWalkedKingdoms($character);
        $this->updateKingdom($kingdom);

        return $this->successResult(['message' => 'All kingdoms walked!']);
    }

    /**
     * Send off building upgrade or repair requests.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $params
     * @param string $type
     * @return array
     */
    public function sendoffBuildingRequests(Character $character, Kingdom $kingdom, array $params, string $type): array
    {
        return $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $kingdom, $params, $type);
    }

    // Private Methods

    /**
     * Ensure only one capital city exists per game plane.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    private function validateOneCapitalCityPerPlane(Kingdom $kingdom): void
    {
        $otherCapitalCitiesCount = Kingdom::where('game_map_id', $kingdom->game_map_id)
            ->where('is_capital', true)
            ->count();

        if ($otherCapitalCitiesCount > 0) {
            $this->errorResult('Cannot have more than one Capital city on plane: ' . $kingdom->gameMap->name);
        }
    }

    /**
     * Update the kingdom to mark it as a capital city.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    private function updateKingdom(Kingdom $kingdom): void
    {
        $this->updateKingdom->updateKingdom($kingdom->refresh());
    }

    /**
     * Retrieve other kingdoms owned by the character.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return EloquentCollection
     */
    private function getOtherKingdoms(Character $character, Kingdom $kingdom)
    {
        return $character->kingdoms()->where('id', '!=', $kingdom->id)->get();
    }

    /**
     * Fetch buildings data from other kingdoms for upgrades or repairs.
     *
     * @param EloquentCollection $kingdoms
     * @return array
     */
    private function fetchBuildingsData($kingdoms): array
    {
        $kingdomBuildingData = [];

        foreach ($kingdoms as $otherKingdom) {
            $buildings = $this->fetchBuildings($otherKingdom);
            $kingdomBuildingData[] = $this->formatKingdomBuildingData($otherKingdom, $buildings);
        }

        return $kingdomBuildingData;
    }

    /**
     * Fetch buildings from a specific kingdom for upgrades or repairs.
     *
     * @param Kingdom $kingdom
     * @return SupportCollection
     */
    private function fetchBuildings(Kingdom $kingdom): SupportCollection
    {
        $buildings = $kingdom->buildings()
            ->join('game_buildings', 'game_buildings.id', '=', 'kingdom_buildings.game_building_id')
            ->where('kingdom_buildings.is_locked', false)
            ->whereNotIn('kingdom_buildings.id', function ($query) use ($kingdom) {
                $query->select('building_id')
                    ->from('buildings_in_queue')
                    ->where('kingdom_id', $kingdom->id);
            })
            ->whereColumn('game_buildings.max_level', '>', 'kingdom_buildings.level')
            ->select('kingdom_buildings.*')
            ->get();

        return $this->filterOutCapitalCityBuildingsInQueue($buildings);

    }

    /**
     * Filters out buildings who are currently in the Capital City Building Queue.
     * 
     * @param EloquentCollection $kingdomBuildings
     * @return SupportCollection
     */
    private function filterOutCapitalCityBuildingsInQueue(EloquentCollection $kingdomBuildings): SupportCollection
    {
        $buildingIds = $kingdomBuildings->pluck('id')->toArray();

        $capitalCityBuildingQueues = CapitalCityBuildingQueue::whereIn('kingdom_id', $kingdomBuildings->pluck('kingdom_id'))
            ->get();

        $invalidBuildingIds = $capitalCityBuildingQueues->flatMap(function ($queue) use ($buildingIds) {
            return collect($queue->building_request_data)->pluck('building_id')->intersect($buildingIds);
        })->unique()->toArray();


        return $kingdomBuildings->reject(function ($building) use ($invalidBuildingIds) {
            return in_array($building->id, $invalidBuildingIds);
        });
    }

    /**
     * Format kingdom and buildings data.
     *
     * @param Kingdom $kingdom
     * @param SupportCollection $buildings
     * @return array
     */
    private function formatKingdomBuildingData(Kingdom $kingdom, SupportCollection $buildings): array
    {
        $buildings = new Collection($buildings, $this->kingdomBuildingTransformer);
        $buildings = $this->manager->createData($buildings)->toArray();

        return [
            'kingdom_id' => $kingdom->id,
            'kingdom_name' => $kingdom->name,
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
            'map_name' => $kingdom->gameMap->name,
            'buildings' => $buildings,
        ];
    }

    /**
     * Retrieve selectable kingdoms for the given kingdom.
     *
     * @param Kingdom $kingdom
     * @return array
     */
    private function getSelectableKingdoms(Kingdom $kingdom): array
    {
        return Kingdom::where('id', '!=', $kingdom->id)
            ->whereDoesntHave('unitsQueue')
            ->select('name', 'id')
            ->get()
            ->toArray();
    }

    /**
     * Update all kingdoms owned by the character as walked.
     *
     * @param Character $character
     * @return void
     */
    private function updateWalkedKingdoms(Character $character): void
    {
        $character->kingdoms()->update([
            'last_walked' => now(),
            'auto_walked' => true,
        ]);
    }

    /**
     * Generate success message for making the kingdom a capital city.
     *
     * @param Kingdom $kingdom
     * @return string
     */
    private function getCapitalCityMessage(Kingdom $kingdom): string
    {
        return 'Your kingdom: ' . $kingdom->name . ' on plane: ' . $kingdom->gameMap->name . ' is now a capital city. ' .
            'You can manage all your cities on this plane from this kingdom. This kingdom will also appear at the top ' .
            'of your kingdom list with a special icon.';
    }
}
