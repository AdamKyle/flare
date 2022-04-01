<?php

namespace App\Game\Kingdoms\Service;

use App\Game\Messages\Events\ServerMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Messages\Events\GlobalMessageEvent;


class KingdomService {

    use KingdomCache;

    /**
     * @var KingdomBuilder $builder
     */
    private $builder;

    private $kingdomTransformer;

    private $manager;

    /**
     * constructor
     *
     * @param KingdomBuilder $builder
     * @return void
     */
    public function __construct(KingdomBuilder $builder, KingdomTransformer $kingdomTransformer, Manager $manager) {
        $this->builder            = $builder;
        $this->kingdomTransformer = $kingdomTransformer;
        $this->manager            = $manager;
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

        $up        = Location::where('x', $x)->where('y', $y - 16)->where('game_map_id', $character->map->game_map_id)->first();
        $down      = Location::where('x', $x)->where('y', $y + 16)->where('game_map_id', $character->map->game_map_id)->first();
        $left      = Location::where('x', $x - 16)->where('y', $y)->where('game_map_id', $character->map->game_map_id)->first();
        $right     = Location::where('x', $x + 16)->where('y', $y)->where('game_map_id', $character->map->game_map_id)->first();
        $upLeft    = Location::where('x', $x - 16)->where('y', $y - 16)->where('game_map_id', $character->map->game_map_id)->first();
        $upRight   = Location::where('x', $x + 16)->where('y', $y - 16)->where('game_map_id', $character->map->game_map_id)->first();
        $downLeft  = Location::where('x', $x - 16)->where('y', $y + 16)->where('game_map_id', $character->map->game_map_id)->first();
        $downRight = Location::where('x', $x + 16)->where('y', $y + 16)->where('game_map_id', $character->map->game_map_id)->first();

        return (is_null($up) && is_null($down) && is_null($left) && is_null($right) &&
            is_null($upLeft) && is_null($upRight) && is_null($downLeft) && is_null($downRight));
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

        broadcast(new UpdateGlobalMap($character));

        $count = $character->refresh()->kingdoms()->count();

        if ($count === 100) {
            // @codeCoverageIgnoreStart
            $message = $character->name . ' Has settled their 100th kingdom. They are becoming unstoppable!';

            broadcast(new GlobalMessageEvent($message));
            // @codeCoverageIgnoreEnd
        }

        if ($count === 500) {
            // @codeCoverageIgnoreStart
            $message = $character->name . ' Has settled their 500th kingdom. The lands choke under their grip.';

            broadcast(new GlobalMessageEvent($message));
            // @codeCoverageIgnoreEnd
        }

        if ($count === 1000) {
            // @codeCoverageIgnoreStart
            $message = $character->name . ' Has settled their 1000th kingdom. Even The Creator trembles in fear.';

            broadcast(new GlobalMessageEvent($message));
            // @codeCoverageIgnoreEnd
        }

        return [];
    }

    /**
     * Embezzle from kingdom.
     *
     * @param Kingdom $kingdom
     * @param $amountToEmbezzle
     */
    public function embezzleFromKingdom(Kingdom $kingdom, $amountToEmbezzle) {
        $newMorale   = $kingdom->current_morale - 0.15;

        $kingdom->update([
            'treasury' => $kingdom->treasury - $amountToEmbezzle,
            'current_morale' => $newMorale,
        ]);

        $character = $kingdom->character;

        $character->update([
            'gold' => $character->gold + $amountToEmbezzle
        ]);

        $kingdom  = new Item($kingdom->refresh(), $this->kingdomTransformer);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        event(new UpdateTopBarEvent($character->refresh()));
        event(new UpdateKingdom($character->user, $kingdom));
    }

    protected function assignKingdomBuildings(Kingdom $kingdom): Kingdom {
        $character = $kingdom->character;

        foreach(GameBuilding::all() as $building) {

            $isLocked  = $building->is_locked;

            if ($isLocked) {
                $passive = $character->passiveSkills()->where('passive_skill_id', $building->passive_skill_id)->first();

                if (!is_null($passive)) {
                    if ($passive->current_level === $building->level_required) {
                        $isLocked = false;
                    }
                }
            }

            $kingdom->buildings()->create([
                'game_building_id'    => $building->id,
                'kingdom_id'          => $kingdom->id,
                'level'               => 1,
                'current_defence'     => $building->base_defence,
                'current_durability'  => $building->base_durability,
                'max_defence'         => $building->base_defence,
                'max_durability'      => $building->base_durability,
                'is_locked'           => $isLocked,
            ]);
        }

        return $kingdom->refresh();
    }
}
