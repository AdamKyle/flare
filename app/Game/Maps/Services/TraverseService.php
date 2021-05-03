<?php

namespace App\Game\Maps\Services;

use App\Game\Maps\Events\MoveTimeOutEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Game\Maps\Events\UpdateActionsBroadcast;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\GameMap;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\MonsterTransfromer;
use App\Flare\Values\ItemEffectsValue;

class TraverseService {

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var CharacterAttackTransformer $characterAttackTransformer
     */
    private $characterAttackTransformer;

    /**
     * @var MonsterTransfromer $monsterTransformer
     */
    private $monsterTransformer;

    /**
     * @var LocationService $locationService
     */
    private $locationService;

    /**
     * TraverseService constructor.
     *
     * @param Manager $manager
     * @param CharacterAttackTransformer $characterAttackTransformer
     * @param MonsterTransfromer $monsterTransformer
     * @param LocationService $locationService
     */
    public function __construct(
        Manager $manager,
        CharacterAttackTransformer $characterAttackTransformer,
        MonsterTransfromer $monsterTransformer,
        LocationService $locationService
    ) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->monsterTransformer         = $monsterTransformer;
        $this->locationService            = $locationService;
    }

    /**
     * Can you travel to another plane?
     *
     * @param int $mapId
     * @param Character $character
     * @return bool
     */
    public function canTravel(int $mapId, Character $character): bool {
        $gameMap = GameMap::find($mapId);

        if ($gameMap->name === 'Labyrinth') {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::LABYRINTH;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->name === 'surface') {
            return true;
        }

        return false;
    }

    /**
     * Travel to another plane of existence.
     *
     * @param int $mapId
     * @param Character $character
     */
    public function travel(int $mapId, Character $character) {
        $character->map()->update([
            'game_map_id' => $mapId
        ]);

        $character = $character->refresh();

        $this->updateMap($character);
        $this->updateActions($mapId, $character);
        $this->updateCharacterTimeOut($character);

        $message = 'You have traveled to: ' . $character->map->gameMap->name;

        event(new ServerMessageEvent($character->user, 'plane-transfer', $message));
    }

    /**
     * Set the timeout for the character.
     *
     * @param Character $character
     */
    protected function updateCharacterTimeOut(Character $character) {
        $character->update([
            'can_move'          => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        event(new MoveTimeOutEvent($character));
    }

    /**
     * Update character actions.
     *
     * @param int $mapId
     * @param Character $character
     */
    protected function updateActions(int $mapId, Character $character) {
        $user      = $character->user;
        $character = new Item($character, $this->characterAttackTransformer);
        $monsters  = new Collection(Monster::where('published', true)->where('game_map_id', $mapId)->orderBy('max_level', 'asc')->get(), $this->monsterTransformer);

        $character = $this->manager->createData($character)->toArray();
        $monster   = $this->manager->createData($monsters)->toArray();

        broadcast(new UpdateActionsBroadcast($character, $monster, $user));
    }

    /**
     * Update the map to reflect the new plane.
     *
     * @param Character $character
     */
    protected function updateMap(Character $character) {

        broadcast(new UpdateMapBroadcast($this->locationService->getLocationData($character), $character->user));
    }
}
