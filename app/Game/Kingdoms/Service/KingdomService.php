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

    /**
     * @var KingdomBuilder $builder
     */
    private $builder;

    /**
     * constructor
     * 
     * @param KingdomBuilder $builder
     * @return void
     */
    public function __construct(KingdomBuilder $builder) {
        $this->builder = $builder;
    }

    /**
     * Sets the params for the kingdom.
     * 
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void {
        $kingdomParams = $params;

        $kingdomParams['color'] = array_values($kingdomParams['color']);

        $this->builder->setRequestAttributes($kingdomParams);
    }

    /**
     * Creates the kingdom for the character.
     * 
     * @param Character $character
     * @return Kingdom
     */
    public function createKingdom(Character $character): Kingdom {
        $kingdom = $this->builder->createKingdom($character);

        return $this->assignBuildings($kingdom);
    }

    /**
     * Can the character settle here?
     * 
     * No if there is a kingdom there.
     * No if there is a location there.
     * 
     * @param int $x
     * @param int $y
     * @return bool
     */
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

    /**
     * Sends off an event to the front end.
     * 
     * This will update the current map to add a kingdom at the players location.
     * 
     * @param Character $character
     * @param Kingdom $kingdom
     * @param KingdomTransformer $transformer
     * @param Manager $manager
     * 
     * @return array
     */
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