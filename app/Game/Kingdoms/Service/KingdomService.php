<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Builders\KingdomBuilder;

class KingdomService {

    private $builder;

    public function __construct(KingdomBuilder $builder) {
        $this->builder = $builder;
    }

    public function setParams(array $params) {
        $kingdomParams = $params;

        $kingdomParams['color'] = array_values($kingdomParams['color']);

        $this->builder->setRequestAttributes($kingdomParams);
    }

    public function createKingdom(Character $character): Kingdom {
        $kingdom = $this->builder->createKingdom($character);

        return $this->assignBuildings($kingdom);
    }

    protected function assignBuildings(Kingdom $kingdom): Kingdom {
        foreach(GameBuilding::all() as $building) {
            $kingdom->buildings()->create([
                'game_building_id'    => $building->id,
                'kingdoms_id'         => $kingdom->id,
                'level'               => 1,
                'current_defence'     => $building->defence,
                'current_durability'  => $building->durability,
                'max_defence'         => $building->defence,
                'max_durability'      => $building->durability,
            ]);
        }

        return $kingdom->refresh();
    }
}