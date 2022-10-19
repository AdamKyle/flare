<?php

namespace App\Game\Kingdoms\Service;

use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Traits\UpdateKingdomBuildingsBasedOnPassives;
use App\Game\Messages\Events\ServerMessageEvent;
use Cache;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Kingdoms\Builders\KingdomBuilder;

class KingdomSettleService {

    use ResponseBuilder, KingdomCache, UpdateKingdomBuildingsBasedOnPassives;

    /**
     * @var KingdomBuilder $kingdomBuilder
     */
    private KingdomBuilder $kingdomBuilder;

    /**
     * @var UpdateKingdomHandler $updateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @var string
     */
    private string $errorMessage;

    /**
     * @param KingdomBuilder $kingdomBuilder
     * @param UpdateKingdomHandler $updateKingdomHandler
     */
    public function __construct(KingdomBuilder $kingdomBuilder, UpdateKingdomHandler $updateKingdomHandler) {
        $this->kingdomBuilder       = $kingdomBuilder;
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string {
        return $this->errorMessage;
    }

    /**
     * @param Character $character
     * @param string $kingdomName
     * @return array
     */
    public function settlePreCheck(Character $character, string $kingdomName): array {
        if (!is_null($character->can_settle_again_at)) {
            return $this->errorResult('You can settle another kingdom in: ' . now()->diffInMinutes($character->can_settle_again_at) . ' Minutes.');
        }

        if ($character->map->gameMap->mapType()->isPurgatory()) {
            return $this->errorResult('Child, this is not place to be a King or Queen, The Creator would destroy anything you build down here.');
        }

        $kingdom = Kingdom::where('name', $kingdomName)->where('game_map_id', $character->map->game_map_id)->first();

        if (!is_null($kingdom)) {
            return $this->errorResult('Name is already taken');
        }

        return [];
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
     * Creates the kingdom for the character.
     *
     * @param Character $character
     * @param string $name
     * @return Kingdom
     */
    public function createKingdom(Character $character, string $name): Kingdom {
        $kingdom = $this->kingdomBuilder->createKingdom($character, $name, $character->map->gameMap->kingdom_color);

        $kingdom = $this->assignKingdomBuildings($kingdom);

        $this->addKingdomToCache($character, $kingdom->refresh());

        $character = $character->refresh();

        if ($character->kingdoms()->count() === 1) {
            event(new ServerMessageEvent($character->user, 'Your kingdom is under protection for 7 days.'));
        }

        return $kingdom;
    }

    /**
     * Purchase a kingdom from the NPC.
     *
     * @param Character $character
     * @param int $kingdomId
     * @param string $name
     * @return Kingdom|null
     */
    public function purchaseKingdom(Character $character, int $kingdomId): ?Kingdom {
        $kingdom = Kingdom::where('id', $kingdomId)->where('npc_owned', true)->first();

        if (is_null($kingdom)) {
            return null;
        }

        $underProtection = false;

        if ($character->kingdoms()->count() === 0) {
            $underProtection = true;
        }

        $params = [
            'character_id'    => $character->id,
            'npc_owned'       => false,
            'protected_until' => $underProtection ? now()->addDays(7) : null,
            'last_walked'     => now(),
        ];

        $kingdom->update($params);

        $kingdom = $this->updateBuildings($kingdom->refresh());

        $this->addKingdomToCache($character, $kingdom);

        if ($underProtection) {
            event(new ServerMessageEvent($character->user, 'Your kingdom is under protection for 7 days.'));
        }

        event(new ServerMessageEvent($character->user, 'Kingdom Purchased.'));

        event(new ServerMessageEvent($character->user, 'The Old Man smiles at you. "Thank you child! This kingdom is all yours now."'));

        event(new UpdateGlobalMap($character));

        return $kingdom->refresh();
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

    /**
     * Adds a kingdom to the cache.
     *
     * If the cache does not exist, we will create the cache.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return array
     */
    public function addKingdomToCache(Character $character, Kingdom $kingdom): array {
        $plane = $character->map->gameMap->name;

        if (Cache::has('character-kingdoms-'  . $plane . '-' . $character->id)) {
            $cache = Cache::get('character-kingdoms-'  . $plane . '-' . $character->id);

            Cache::put('character-kingdoms-'  . $plane . '-' . $character->id, $this->addKingdom($kingdom, $cache));

            return Cache::get('character-kingdoms-'  . $plane . '-' . $character->id);
        }

        Cache::put('character-kingdoms-'  . $plane . '-' . $character->id, $this->addKingdom($kingdom));

        return Cache::get('character-kingdoms-'  . $plane . '-' . $character->id);
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

        event(new UpdateGlobalMap($character));

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

        return [
            'message' => 'Settled a new kingdom! Check your Kingdoms tab.',
        ];
    }
}
