<?php

namespace App\Game\Maps\Validation;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;

class CanTravelToMap {

    /**
     * Can we travel to this new map?
     *
     * @param int $mapId
     * @param Character $character
     * @return bool
     */
    public function canTravel(int $mapId, Character $character): bool {
        $gameMap = GameMap::find($mapId);

        $canTravelToLabyrinth   = $this->canTravelToLabyrinth($gameMap, $character);
        $canTravelToDungeons    = $this->canTravelToDungeons($gameMap, $character);
        $canTravelToShadowPlane = $this->canTravelToShadowPlanes($gameMap, $character);
        $canTravelToHell        = $this->canTravelToHell($gameMap, $character);
        $canTravelToPurgatory   = $this->canTravelToPurgatory($gameMap, $character);

        if (!is_null($canTravelToLabyrinth)) {
            return $canTravelToLabyrinth;
        }

        if (!is_null($canTravelToDungeons)) {
            return $canTravelToDungeons;
        }

        if (!is_null($canTravelToShadowPlane)) {
            return $canTravelToShadowPlane;
        }

        if (!is_null($canTravelToHell)) {
            return $canTravelToHell;
        }

        if (!is_null($canTravelToPurgatory)) {
            return $canTravelToPurgatory;
        }

        // If here, we are going to surface.
        return true;
    }

    protected function canTravelToLabyrinth(GameMap $gameMap, Character $character): mixed {
        if ($gameMap->mapType()->isLabyrinth()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::LABYRINTH;
            })->all();

            return !empty($hasItem);
        }

        return null;
    }

    protected function canTravelToDungeons(GameMap $gameMap, Character $character): mixed {
        if ($gameMap->mapType()->isDungeons()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::DUNGEON;
            })->all();

            return !empty($hasItem);
        }

        return null;
    }

    protected function canTravelToShadowPlanes(GameMap $gameMap, Character $character): mixed  {
        if ($gameMap->mapType()->isShadowPlane()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::SHADOWPLANE;
            })->all();

            return !empty($hasItem);
        }

        return null;
    }

    protected function canTravelToHell(GameMap $gameMap, Character $character): mixed {
        if ($gameMap->mapType()->isHell()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::HELL;
            })->all();

            return !empty($hasItem);
        }

        return null;
    }

    protected function canTravelToPurgatory(GameMap $gameMap, Character $character): mixed {
        if ($gameMap->mapType()->isPurgatory()) {
            $hasItem = $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::PURGATORY;
            })->all();

            return !empty($hasItem);
        }

        return null;
    }
}
