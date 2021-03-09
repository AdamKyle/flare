<?php

namespace App\Game\Kingdoms\Service;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Transformers\SelectedKingdom;

class KingdomsAttackService {

    use ResponseBuilder;

    public function __construct(SelectedKingdom $selectedKingdom, Manager $manager) {
        $this->selectedKingdom = $selectedKingdom;
        $this->manager         = $manager;
    }

    public function fetchSelectedKingdomData(Character $character, array $kingdoms): array {
        $kingdomData = [];
        
        foreach ($kingdoms as $kingdomId) {
            $kingdom = Kingdom::where('character_id', $character->id)->where('id', $kingdomId)->first();

            if (is_null($kingdom)) {
                return $this->errorResult('You do not own this kingdom.');
            }

            $kingdom = new Item($kingdom, $this->selectedKingdom);
            $kingdom = $this->manager->createData($kingdom)->toArray();
            
            $kingdomData[] = $kingdom;
        }

        return $this->successResult($kingdomData);
    }
}