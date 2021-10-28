<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Maps\Jobs\PCTTeleport;
use App\Game\Maps\Jobs\PCTTraverse;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Bus;

class PctService {

    public function usePCT(Character $character, bool $teleport = false): bool {
        $celestialFight = $this->findCelestialFight($character);

        if (is_null($celestialFight)) {
             return false;
        }

        $map          = $celestialFight->monster->gameMap;
        $x            = $celestialFight->x_position;
        $y            = $celestialFight->y_position;

        if (!$teleport) {

            $message = 'Child! ' . $celestialFight->monster->name  .' is at (X/Y): '. $x .'/'. $y. ' on the: '. $map->name .'Plane.';

            broadcast(new ServerMessageEvent($character->user, $message));

            return true;
        }

        $characterMap = $character->map->gameMap;

        if ($this->isCharacterOnTheSameMap($map->name, $characterMap->name)) {
            $message = 'Child! I am processing your request.';
            broadcast(new ServerMessageEvent($character->user, $message));

            PCTTeleport::dispatch($character, $x, $y, $celestialFight->monster->name, $map->name);
        } else {
            $message = 'Child! I am processing your request.';
            broadcast(new ServerMessageEvent($character->user, $message));

            PCTTraverse::dispatch($character, $map, $celestialFight, $x, $y);
        }

        return true;
    }

    protected function findCelestialFight(Character $character): ?CelestialFight {
        $celestial = CelestialFight::where('type', CelestialConjureType::PUBLIC)->first();

        if (is_null($celestial)) {
            $celestial = CelestialFight::where('type', CelestialConjureType::PRIVATE)->where('character_id', $character->id)->first();
        }

        return $celestial;
    }

    protected function isCharacterOnTheSameMap(string $celestialMapName, string $characterMapName): bool {
        return $celestialMapName === $characterMapName;
    }
}