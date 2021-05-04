<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class KingdomService {

    use KingdomCache;

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
     * @param Character $character
     * @return void
     */
    public function setParams(array $params, Character $character): void {
        $params['color'] = $character->map->gameMap->kingdom_color;

        $this->builder->setRequestAttributes($params);
    }

    /**
     * Creates the kingdom for the character.
     *
     * @param Character $character
     * @return Kingdom
     */
    public function createKingdom(Character $character): Kingdom {
        $kingdom = $this->builder->createKingdom($character);

        $kingdom = $this->assignKingdomBuildings($kingdom);

        $this->addKingdomToCache($character, $kingdom);

        return $kingdom;
    }

    /**
     * Can the character settle here?
     *
     * No if there is a kingdom there.
     * No if there is a location there.
     *
     * @param int $x
     * @param int $y
     * @param Character $character
     * @return bool
     */
    public function canSettle(int $x, int $y, Character $character): bool {
        $kingdom = Kingdom::where('x_position', $x)->where('y_position', $y)->where('game_map_id', $character->map->game_map_id)->first();

        if (!is_null($kingdom)) {
            return false;
        }

        $location = Location::where('x', $x)->where('y', $y)->where('game_map_id', $character->map->game_map_id)->first();

        if (!is_null($location)) {
            return false;
        }

        return true;
    }

    /**
     * Can you afford to settle here?
     *
     * @param int $amount
     * @param Character $character
     * @return bool
     */
    public function canAfford(int $amount, Character $character): bool {
        if ($amount > 0) {
            $amount *= 10000;

            if ($character->gold < $amount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sends off an event to the front end.
     *
     * This will update the current map to add a kingdom at the players location.
     *
     * @param Character $character
     * @return array
     */
    public function addKingdomToMap(Character $character): array {
        event(new AddKingdomToMap($character));

        return [];
    }

    protected function assignKingdomBuildings(Kingdom $kingdom): Kingdom {
        foreach(GameBuilding::all() as $building) {
            $kingdom->buildings()->create([
                'game_building_id'    => $building->id,
                'kingdom_id'          => $kingdom->id,
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
