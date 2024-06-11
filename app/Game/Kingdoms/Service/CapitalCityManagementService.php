<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;

class CapitalCityManagementService {

    use ResponseBuilder;

    public function __construct(private readonly UpdateKingdom $updateKingdom){}

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
}
