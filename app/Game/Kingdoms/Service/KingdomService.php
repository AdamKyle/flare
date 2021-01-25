<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Building;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

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

    public function canSettle(int $x, int $y): bool {
        $kingdom = Kingdom::where('x_position', $x)->where('y_position', $y)->first();
        
        if (!is_null($kingdom)) {
            return false;
        }

        $location = Location::where('x', $x)->where('y', $y)->first();
        
        if (!is_null($location)) {
            return false;
        }

        return true;
    }

    public function addKingdomToMap(Character $character, Kingdom $kingdom, KingdomTransformer $transfromer, Manager $manager): array {
        $kingdom  = new Item($kingdom, $transfromer);

        $kingdom = $manager->createData($kingdom)->toArray();

        event(new AddKingdomToMap($character->user, $kingdom));

        return $kingdom;
    }

    protected function assignBuildings(Kingdom $kingdom): Kingdom {
        foreach(GameBuilding::all() as $building) {
            $kingdom->buildings()->create([
                'game_building_id'    => $building->id,
                'kingdoms_id'         => $kingdom->id,
                'level'               => 1,
                'current_defence'     => $building->base_defence,
                'current_durability'  => $building->base_durability,
                'max_defence'         => $building->base_defence,
                'max_durability'      => $building->base_durability,
            ]);
        }

        return $kingdom->refresh();
    }
}