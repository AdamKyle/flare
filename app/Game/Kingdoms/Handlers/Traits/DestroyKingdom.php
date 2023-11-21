<?php

namespace App\Game\Kingdoms\Handlers\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Messages\Events\GlobalMessageEvent;

Trait DestroyKingdom {

    use KingdomCache;

    /**
     * Destroy a kingdom.
     *
     * - Updates the maps.
     * - If it is a character kingdom, refresh them to update their kingdoms.
     *
     * @param Kingdom $kingdom
     * @param Character|null $character
     * @return void
     */
    public function destroyKingdom(Kingdom $kingdom, ?Character $character = null): void {

        $x       = $kingdom->x_position;
        $y       = $kingdom->y_position;
        $gameMap = $kingdom->gameMap;

        $kingdom->buildingsQueue()->delete();
        $kingdom->unitsMovementQueue()->delete();
        $kingdom->unitsQueue()->delete();
        $kingdom->units()->delete();
        $kingdom->buildings()->delete();

        $kingdom->refresh()->delete();

        event(new GlobalMessageEvent('A kingdom at: (X/Y) ' .
            $x . '/' . $y . ' on ' .
            $gameMap->name .' Plane has crumbled to the earth clearing up space for a new kingdom'
        ));

        if (!is_null($character)) {
            $character = $character->refresh();

            $this->rebuildCharacterKingdomCache($character);

            event(new UpdateGlobalMap($character));
            event(new AddKingdomToMap($character));
        }

        if (is_null($character)) {
            event(new UpdateNPCKingdoms($gameMap));
        }
    }
}
