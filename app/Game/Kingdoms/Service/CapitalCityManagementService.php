<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomBuildingTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use DB;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class CapitalCityManagementService {

    use ResponseBuilder;

    public function __construct(
        private readonly UpdateKingdom $updateKingdom,
        private readonly KingdomBuildingTransformer $kingdomBuildingTransformer,
        private readonly Manager $manager
    ) {}

    public function makeCapitalCity(Kingdom $kingdom): array {

        $otherCapitalCitiesCount = Kingdom::where('game_map_id', $kingdom->game_map_id)->where('is_capital', true)->count();

        if ($otherCapitalCitiesCount > 0) {
            return $this->errorResult('Cannot have more then one Capital city on plane: ' . $kingdom->gameMap->name);
        }

        $kingdom->update(['is_capital' => true]);

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return $this->successResult([
            'message' => 'Your kingdom: ' . $kingdom->name . ' on plane: ' . $kingdom->gameMap->name . ' is now a capital city. ' .
                'You can manage all your cities on this plane from this kingdom. This kingdom will also appear at the top ' .
                'of your kingdom list with a special icon.',
        ]);
    }

    public function fetchBuildingsForUpgradesOrRepairs(Character $character, Kingdom $kingdom): array {

        $kingdoms = $character->kingdoms()->where('id', '!=', $kingdom->id)->get();

        $kingdomBuildingData = [];

        foreach ($kingdoms as $kingdom) {

            $buildings = $kingdom->buildings()
                ->where('is_locked', false)
                ->whereNotIn('id', function ($query) use ($kingdom) {
                    $query->select('building_id')
                        ->from('buildings_in_queue')
                        ->where('kingdom_id', $kingdom->id);
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('game_buildings')
                        ->whereColumn('game_buildings.id', 'kingdom_buildings.game_building_id')
                        ->whereColumn('game_buildings.max_level', '!=', 'kingdom_buildings.level');
                })
                ->get();

            $buildings = new Collection($buildings, $this->kingdomBuildingTransformer);
            $buildings = $this->manager->createData($buildings)->toArray();

            $kingdomBuildingData[] = [
                'kingdom_id' => $kingdom->id,
                'kingdom_name' => $kingdom->name,
                'x_position' => $kingdom->x_position,
                'y_position' => $kingdom->y_position,
                'map_name' => $kingdom->gameMap->name,
                'buildings' => $buildings,
            ];
        }

        return $this->successResult($kingdomBuildingData);
    }

    public function fetchKingdomsForSelection(Character $character, Kingdom $kingdom): array {
        return $this->successResult([
            'kingdoms' => Kingdom::where('id', '!=', $kingdom->id)->select('name', 'id')->get()->toArray(),
        ]);
    }

    public function walkAllKingdoms(Character $character, Kingdom $kingdom): array {

        $character->kingdoms()->update([
            'last_walked' => now(),
            'auto_walked' => true,
        ]);

        $this->updateKingdom->updateKingdom($kingdom);

        return $this->successResult([
            'message' => 'All kingdoms walked!'
        ]);
    }
}
