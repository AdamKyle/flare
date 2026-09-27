<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Values\MapName;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;

class PctService
{
    /**
     * @param TraverseService $traverseService
     * @param MapTileValue $mapTileValue
     * @param AutomationRestrictionService $automationRestrictionService
     * @param LocationService $locationService
     */
    public function __construct(
        private readonly TraverseService $traverseService,
        private readonly MapTileValue $mapTileValue,
        private readonly AutomationRestrictionService $automationRestrictionService,
        private readonly LocationService $locationService,
    ) {}

    /**
     * Use the /pc or /pct chat command to locate, and optionally teleport to, a Celestial.
     *
     * @param Character $character
     * @param bool $teleport
     * @return bool
     */
    public function usePCT(Character $character, bool $teleport = false): bool
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::PCT);

        if (! is_null($restriction)) {
            event(new ServerMessageEvent($character->user, $restriction['message']));

            return false;
        }

        if ($teleport && ! $character->can_move) {
            event(new ServerMessageEvent($character->user, 'Sorry child, you are exhausted from your last move, wait for the timer'));

            return true;
        }

        $celestialFight = $this->findCelestialFight($character);

        $this->mapTileValue->setUp($character, $character->map->gameMap);

        if (is_null($celestialFight)) {
            return false;
        }

        if (! $teleport) {
            $this->sendDirections($character, $celestialFight);

            return true;
        }

        $moved = $this->moveToCelestial($character, $celestialFight);

        if (! $moved) {
            return false;
        }

        event(new UpdateMapDetailsBroadcast($character->user, $this->locationService));

        return true;
    }

    /**
     * Move the Character to the Celestial, traversing planes when the Celestial is on another plane.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return bool
     */
    private function moveToCelestial(Character $character, CelestialFight $celestialFight): bool
    {
        if ($celestialFight->gameMapName() === $character->map->gameMap->name) {
            return $this->teleportOnCurrentPlane($character, $celestialFight);
        }

        return $this->teleportToCelestialPlane($character, $celestialFight);
    }

    /**
     * Teleport the Character to the Celestial on the plane they are already on.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return bool
     */
    private function teleportOnCurrentPlane(Character $character, CelestialFight $celestialFight): bool
    {
        if (! $this->mapTileValue->canWalk($celestialFight->x_position, $celestialFight->y_position)) {
            event(new ServerMessageEvent($character->user, 'Child. You are missing the required item to travel to this location.'));

            return false;
        }

        $character->map()->update([
            'character_position_x' => $celestialFight->x_position,
            'character_position_y' => $celestialFight->y_position,
        ]);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'Child! I have done it. I have used the magics to move you to: (X/Y) '.$celestialFight->x_position.'/'.$celestialFight->y_position));

        return true;
    }

    /**
     * Teleport the Character onto the plane the Celestial is on.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return bool
     */
    private function teleportToCelestialPlane(Character $character, CelestialFight $celestialFight): bool
    {
        event(new ServerMessageEvent($character->user, 'The magics in the air crackle, your body begins to be dragged through the portal ...'));

        if (! $this->traverseService->canTravel($celestialFight->monster->gameMap->id, $character)) {
            event(new ServerMessageEvent($character->user, 'Child. You are missing the required item to travel to this plane.'));

            return false;
        }

        $oldMapId = $character->map->game_map_id;

        if (! $this->moveToCelestialPlane($character, $celestialFight, $oldMapId)) {
            return false;
        }

        $this->handleNewPlaneUpdate($character, $celestialFight, $oldMapId);

        return true;
    }

    /**
     * Place the Character at the Celestial's coordinates on the new plane and refresh plane state.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @param int $oldMapId
     * @return void
     */
    private function handleNewPlaneUpdate(Character $character, CelestialFight $celestialFight, int $oldMapId): void
    {
        $character->map()->update([
            'character_position_x' => $celestialFight->x_position,
            'character_position_y' => $celestialFight->y_position,
        ]);

        $character = $character->refresh();

        $this->rebuildCharacterStats($character, $oldMapId);

        $this->traverseService->updateActions($character->map->game_map_id, $character, GameMap::find($oldMapId));

        event(new UpdateMap($character->user));

        event(new ServerMessageEvent($character->user, 'Child! I have done it. I have used the magics to move you to: (X/Y) '.$celestialFight->x_position.'/'.$celestialFight->y_position.' on the plane: '.$celestialFight->monster->gameMap->name));
    }

    /**
     * Move the Character onto the Celestial's plane, reverting when the Celestial's tile cannot be walked on.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @param int $oldMapId
     * @return bool
     */
    private function moveToCelestialPlane(Character $character, CelestialFight $celestialFight, int $oldMapId): bool
    {
        $character->map()->update([
            'game_map_id' => $celestialFight->monster->game_map_id,
        ]);

        $character = $character->refresh();

        if (! $this->mapTileValue->canWalk($celestialFight->x_position, $celestialFight->y_position)) {
            $character->map()->update([
                'game_map_id' => $oldMapId,
            ]);

            event(new ServerMessageEvent($character->user, 'Child. You are missing the required item to travel on this planes water surface.'));

            return false;
        }

        return true;
    }

    /**
     * Rebuild the Character's attack cache when moving into or out of Hell or Purgatory.
     *
     * @param Character $character
     * @param int $comingFromMapId
     * @return void
     */
    private function rebuildCharacterStats(Character $character, int $comingFromMapId): void
    {
        $currentMapType = $character->map->gameMap->mapType();
        $previousMapType = GameMap::find($comingFromMapId)->mapType();

        $requiresRebuild = $currentMapType->isHell()
            || $currentMapType->isPurgatory()
            || $previousMapType->isHell()
            || $previousMapType->isPurgatory();

        if (! $requiresRebuild) {
            return;
        }

        CharacterAttackTypesCacheBuilder::dispatch($character)->delay(now()->addSeconds(2));
    }

    /**
     * Tell the Character where the Celestial currently is.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return void
     */
    private function sendDirections(Character $character, CelestialFight $celestialFight): void
    {
        $message = 'Child! '.$celestialFight->monster->name.' is at (X/Y): '.$celestialFight->x_position.'/'.$celestialFight->y_position.' on the: '.$celestialFight->monster->gameMap->name.'Plane.';

        broadcast(new ServerMessageEvent($character->user, $message));
    }

    /**
     * Find the Character's own private Celestial, otherwise a public Celestial the Character can reach.
     *
     * @param Character $character
     * @return ?CelestialFight
     */
    private function findCelestialFight(Character $character): ?CelestialFight
    {
        $privateCelestial = CelestialFight::where('type', CelestialConjureType::PRIVATE)->where('character_id', $character->id)->first();

        if (! is_null($privateCelestial)) {
            return $privateCelestial;
        }

        $publicCelestial = CelestialFight::where('type', CelestialConjureType::PUBLIC)->first();

        if (is_null($publicCelestial)) {
            return null;
        }

        $eventMapIds = GameMap::whereIn('name', [MapName::DELUSIONAL_MEMORIES->value])->pluck('id')->all();

        if (! in_array($publicCelestial->monster->game_map_id, $eventMapIds, true)) {
            return $publicCelestial;
        }

        if ($this->hasEventMapAccessItem($character)) {
            return $publicCelestial;
        }

        return CelestialFight::where('type', CelestialConjureType::PUBLIC)
            ->whereHas('monster', function ($query) use ($eventMapIds) {
                $query->whereNotIn('game_map_id', $eventMapIds);
            })
            ->first();
    }

    /**
     * Determine whether the Character holds the quest item granting access to event-map Celestials.
     *
     * @param Character $character
     * @return bool
     */
    private function hasEventMapAccessItem(Character $character): bool
    {
        return $character->inventory->slots->contains(function ($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectType::PURGATORY->value;
        });
    }
}
