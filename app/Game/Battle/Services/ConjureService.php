<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\NpcMessageTypes;
use Exception;
use Facades\App\Flare\Cache\CoordinatesCache;

class ConjureService
{
    private NpcServerMessageBuilder $npcServerMessageBuilder;

    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder)
    {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    /**
     * Conjure on movement.
     */
    public function movementConjure(Character $character): void
    {

        if (CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty()) {
            return;
        }

        $x = $this->getXPosition();
        $y = $this->getYPosition();

        $monster = $this->createCelestialRecord($x, $y, [MapNameValue::ICE_PLANE]);

        $types = ['has awoken', 'has angered', 'has enraged', 'has set free', 'has set loose'];
        $randomIndex = rand(0, count($types) - 1);
        $plane = $monster->gameMap->name;

        event(new GlobalMessageEvent($character->name . ' ' . $types[$randomIndex] . ': ' . $monster->name . ' on the ' . $plane . ' plane at (X/Y): ' . $x . '/' . $y));
    }

    /**
     * Paid to conjure the beast.
     *
     * @throws Exception
     */
    public function conjure(Monster $monster, Character $character, string $type): void
    {
        $healthRange = explode('-', $monster->health_range);
        $currentMonsterHealth = rand($healthRange[0], $healthRange[1]);

        $x = $this->getXPosition();
        $y = $this->getYPosition();

        $celestialFight = CelestialFight::create([
            'monster_id' => $monster->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => $x,
            'y_position' => $y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => $currentMonsterHealth,
            'max_health' => $currentMonsterHealth,
            'type' => $type === 'private' ? CelestialConjureType::PRIVATE : CelestialConjureType::PUBLIC,
        ]);

        $type = new CelestialConjureType($type === 'private' ? CelestialConjureType::PRIVATE : CelestialConjureType::PUBLIC);
        $npc = Npc::where('type', NpcTypes::SUMMONER)->first();
        $plane = $character->map->gameMap->name;

        broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build(NpcMessageTypes::LOCATION_OF_CONJURE, $npc, $celestialFight)));

        if ($type->isPublic()) {
            event(new GlobalMessageEvent($monster->name . ' has been conjured to the ' . $plane . ' plane.'));
        }

        event(new UpdateMap($character->user));
    }

    /**
     * Are we able to conjure?
     *
     * - Used when purchasing a conjuration.
     */
    public function canConjure(Character $character, Npc $npc, string $type): bool
    {

        if (! $character->can_engage_celestials) {
            return false;
        }

        if (CelestialFight::where('character_id', $character->id)->get()->isNotEmpty()) {
            event(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build(NpcMessageTypes::ALREADY_CONJURED, $npc)));

            return false;
        }

        if ($type === 'public' && CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty()) {
            event(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build(NpcMessageTypes::PUBLIC_CONJURATION_EXISTS, $npc)));

            return false;
        }

        return true;
    }

    /**
     * Can we afford the cost?
     *
     * - Only used when purchasing.
     *
     * @return bool
     */
    public function canAfford(Monster $monster, Character $character)
    {
        if ($monster->gold_cost > $character->gold || $monster->gold_dust_cost > $character->gold_dust) {
            return false;
        }

        return true;
    }

    /**
     * Pay the cost.
     *
     * - Only used when purchasing.
     */
    public function handleCost(Monster $monster, Character $character): void
    {
        $character->update([
            'gold' => $character->gold - $monster->gold_cost,
            'gold_dust' => $character->gold_dust - $monster->gold_dust_cost,
        ]);

        $user = $character->user;
        $characterMapId = $character->map->game_map_id;
        $npc = Npc::where('type', NpcTypes::SUMMONER)->where('game_map_id', $characterMapId)->first();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build(NpcMessageTypes::PAID_CONJURING, $npc)));
    }

    /**
     * Get random X position for the beast.
     */
    protected function getXPosition(): int
    {
        return CoordinatesCache::getFromCache()['x'][rand(CoordinatesCache::getFromCache()['x'][0], (count(CoordinatesCache::getFromCache()['x']) - 1))];
    }

    /**
     * Get random Y  position for the beast.
     */
    protected function getYPosition(): int
    {
        return CoordinatesCache::getFromCache()['y'][rand(CoordinatesCache::getFromCache()['y'][0], (count(CoordinatesCache::getFromCache()['y']) - 1))];
    }

    private function isEventWithCelestialsRunning(): bool
    {
        $eventsWithCelestials = [EventType::DELUSIONAL_MEMORIES_EVENT];

        return Event::whereIn('type', $eventsWithCelestials)->count() > 0;
    }

    private function isCelestialFromEventMap(Event $event, Monster $monster): bool
    {
        $monsterGameMapId = $monster->game_map_id;

        $eventType = new EventType($event->type);

        $gameMapId = null;

        if ($eventType->isDelusionalMemoriesEvent()) {
            $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();

            if (is_null($gameMap)) {
                return false;
            }

            $gameMapId = $gameMap->id;
        }

        return $monsterGameMapId === $gameMapId;
    }

    private function createCelestialRecord(int $x, int $y, array $invalidMapNames = []): Monster
    {

        $invalidMaps = GameMap::whereIn('name', $invalidMapNames)->pluck('id')->toArray();

        $monster = Monster::where('is_celestial_entity', true)
            ->whereNotIn('game_map_id', $invalidMaps)
            ->whereNull('celestial_type')
            ->inRandomOrder()
            ->first();

        $healthRange = explode('-', $monster->health_range);
        $currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;

        CelestialFight::create([
            'monster_id' => $monster->id,
            'character_id' => null,
            'conjured_at' => now(),
            'x_position' => $x,
            'y_position' => $y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => $currentMonsterHealth,
            'max_health' => $currentMonsterHealth,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        return $monster;
    }
}
