<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Bus\PendingDispatch;

class PctService
{
    private TraverseService $traverseService;

    private MapTileValue $mapTileValue;

    public function __construct(TraverseService $traverseService, MapTileValue $mapTileValue)
    {
        $this->traverseService = $traverseService;
        $this->mapTileValue = $mapTileValue;
    }

    /**
     * Use PCT chat command.
     */
    public function usePCT(Character $character, bool $teleport = false): bool
    {
        $celestialFight = $this->findCelestialFight($character);

        if (is_null($celestialFight)) {
            return false;
        }

        $gotDirections = $this->getDirections($character, $celestialFight, $teleport);

        if ($gotDirections) {
            return $gotDirections;
        }

        if ($this->isCharacterOnTheSameMap($celestialFight->gameMapName(), $character->map->gameMap->name)) {

            if (! $this->mapTileValue->canWalk($character, $celestialFight->x_position, $celestialFight->y_position)) {
                event(new ServerMessageEvent($character->user, 'Child. You are missing the required item to travel to this location.'));

                return false;
            }

            $this->teleportToCelestial($character, $celestialFight);
        } else {
            event(new ServerMessageEvent($character->user, 'The magics in the air crackle, your body begins to be dragged through the portal ...'));

            if (! $this->traverseService->canTravel($celestialFight->monster->gameMap->id, $character)) {
                event(new ServerMessageEvent($character->user, 'Child. You are missing the required item to travel to this plane.'));

                return false;
            }

            $oldMapId = $character->map->game_map_id;

            $moveToPlaneResult = $this->moveToCelestialPlane($character, $celestialFight, $oldMapId);

            if (! $moveToPlaneResult) {
                return false;
            }

            $this->handleNewPlaneUpdate($character, $celestialFight, $oldMapId);
        }

        return true;
    }

    /**
     * Handle what happens when the plane update.
     *
     * @return void
     */
    protected function handleNewPlaneUpdate(Character $character, CelestialFight $celestialFight, int $oldMapId)
    {
        $character->map()->update([
            'character_position_x' => $celestialFight->x_position,
            'character_position_y' => $celestialFight->y_position,
        ]);

        $character = $character->refresh();

        $this->rebuildCharacterStats($character, $oldMapId);

        $this->traverseService->updateActions($character->map->game_map_id, $character, GameMap::find($oldMapId));

        event(new UpdateMap($character->user));

        event(new ServerMessageEvent($character->user, 'Child! I have done it. I have used the magics to move you to: (X/Y) ' . $celestialFight->x_position . '/' . $celestialFight->y_position . ' on the plane: ' . $celestialFight->monster->gameMap->name));
    }

    /**
     * Move the character to the p;ane the celestial is on.
     */
    protected function moveToCelestialPlane(Character $character, CelestialFight $celestialFight, int $oldMapId): bool
    {
        $character->map()->update([
            'game_map_id' => $celestialFight->monster->game_map_id,
        ]);

        $character = $character->refresh();

        if (! $this->mapTileValue->canWalk($character, $celestialFight->x_position, $celestialFight->y_position)) {
            $character->map()->update([
                'game_map_id' => $oldMapId,
            ]);

            event(new ServerMessageEvent($character->user, 'Child. You are missing the required item to travel on this planes water surface.'));

            return false;
        }

        return true;
    }

    /**
     * Teleport the player to the celestial location.
     *
     * @return void
     */
    protected function teleportToCelestial(Character $character, CelestialFight $celestialFight)
    {
        $character->map()->update([
            'character_position_x' => $celestialFight->x_position,
            'character_position_y' => $celestialFight->y_position,
        ]);

        $character = $character->refresh();

        event(new UpdateMap($character->user, true));

        event(new ServerMessageEvent($character->user, 'Child! I have done it. I have used the magics to move you to: (X/Y) ' . $celestialFight->x_position . '/' . $celestialFight->y_position));
    }

    /**
     * Do we have to rebuild the character stats?
     *
     * @return PendingDispatch|void
     */
    protected function rebuildCharacterStats(Character $character, int $comingFromMapId)
    {
        if ($character->map->gameMap->mapType()->isHell()) {
            return CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));
        }

        if ($character->map->gameMap->mapType()->isPurgatory()) {
            return CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));
        }

        $gameMap = GameMap::find($comingFromMapId);

        if (($gameMap->mapType()->isHell() || $gameMap->mapType()->isPurgatory())) {
            return CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));
        }
    }

    /**
     * When the character uses /pc command.
     *
     * The /pc command will only give the player the location of the celestial fight.
     *
     * @return bool
     */
    protected function getDirections(Character $character, CelestialFight $celestialFight, bool $teleport)
    {
        $map = $celestialFight->monster->gameMap;
        $x = $celestialFight->x_position;
        $y = $celestialFight->y_position;

        if (! $teleport) {

            $message = 'Child! ' . $celestialFight->monster->name . ' is at (X/Y): ' . $x . '/' . $y . ' on the: ' . $map->name . 'Plane.';

            broadcast(new ServerMessageEvent($character->user, $message));

            return true;
        }

        return false;
    }

    /**
     * Find either public or private celestial (that you own).
     */
    protected function findCelestialFight(Character $character): ?CelestialFight
    {
        $celestial = CelestialFight::where('type', CelestialConjureType::PRIVATE)->where('character_id', $character->id)->first();

        if (is_null($celestial)) {
            $celestial = CelestialFight::where('type', CelestialConjureType::PUBLIC)->first();

            $eventMapNames = [MapNameValue::DELUSIONAL_MEMORIES];

            if (is_null($celestial)) {
                return null;
            }

            if (in_array($celestial->monster->game_map_id, $eventMapNames)) {
                $questItemSlot = $character->inventory->slots->filter(function ($slot) {
                    return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::PURGATORY;
                })->first();

                if (is_null($questItemSlot)) {
                    $celestial = CelestialFight::where('type', CelestialConjureType::PUBLIC)
                        ->whereHas('monster', function ($query) use ($eventMapNames) {
                            $query->whereNotIn('game_map_id', $eventMapNames);
                        })
                        ->first();
                }
            }
        }

        return $celestial;
    }

    /**
     * Does the character need to use traverse?
     */
    protected function isCharacterOnTheSameMap(string $celestialMapName, string $characterMapName): bool
    {
        return $celestialMapName === $characterMapName;
    }
}
