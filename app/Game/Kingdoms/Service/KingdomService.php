<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Messages\Events\GlobalMessageEvent;

class KingdomService {

    use KingdomCache;

    /**
     * @var KingdomBuilder $builder
     */
    private KingdomBuilder$builder;

    /**
     * @var UpdateKingdomHandler $updateKingdomHandle
     */
    private updateKingdomHandler $updateKingdomHandle;

    /**
     * @var string $errorMessage
     */
    private string $errorMessage;

    /**
     * constructor
     *
     * @param KingdomBuilder $builder
     * @param UpdateKingdomHandler $updateKingdomHandler
     */
    public function __construct(KingdomBuilder $builder, UpdateKingdomHandler $updateKingdomHandler) {
        $this->builder             = $builder;
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

    /**
     * Creates the kingdom for the character.
     *
     * @param Character $character
     * @param string $name
     * @return Kingdom
     */
    public function createKingdom(Character $character, string $name): Kingdom {
        $kingdom = $this->builder->createKingdom($character, $name, $character->map->gameMap->kingdom_color);

        $kingdom = $this->assignKingdomBuildings($kingdom);

        $this->addKingdomToCache($character, $kingdom);

        return $kingdom;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string {
        return $this->errorMessage;
    }

    /**
     * Can the character settle here?
     *
     * - No if there is a kingdom there.
     * - No if there is a location there.
     *
     * @param Character $character
     * @return bool
     */
    public function canSettle(Character $character): bool {
        $x = $character->map->character_position_x;
        $y = $character->map->character_position_y;

        $kingdom = Kingdom::where('x_position', $x)->where('y_position', $y)->where('game_map_id', $character->map->game_map_id)->first();

        if (!is_null($kingdom)) {

            $this->errorMessage = 'You are not allowed to settle on top of another kingdom.';

            return false;
        }

        $location = Location::where('x', $x)->where('y', $y)->where('game_map_id', $character->map->game_map_id)->first();

        if (!is_null($location)) {
            $this->errorMessage = 'You are too close to this location, you must be two steps away in any direction.';

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

        $canSettle = (is_null($up) && is_null($down) && is_null($left) && is_null($right) &&
            is_null($upLeft) && is_null($upRight) && is_null($downLeft) && is_null($downRight));

        if (!$canSettle) {
            $this->errorMessage = 'You are too close to this location, you must be two steps away in any direction.';

            return false;
        }

        return $canSettle;
    }

    /**
     * Can you afford to settle here?
     *
     * @param Character $character
     * @return bool
     */
    public function canAfford(Character $character): bool {
        $amount = $character->kingdoms->count();

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

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

        return [
            'message' => 'Settled a new kingdom! Check your Kingdoms tab.',
        ];
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

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

        event(new UpdateTopBarEvent($character->refresh()));
    }

    /**
     * Assign default buildings to kingdom.
     *
     * @param Kingdom $kingdom
     * @return Kingdom
     */
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
