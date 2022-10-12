<?php

namespace App\Game\Battle\Services;

use App\Game\Maps\Events\UpdateMap;
use Facades\App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\GameMap;
use App\Flare\Values\CelestialType;
use App\Flare\Values\MapNameValue;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Maps\Services\LocationService;

class ConjureService {

    /**
     * @var NpcServerMessageBuilder $npcServerMessageBuilder
     */
    private NpcServerMessageBuilder $npcServerMessageBuilder;

    /**
     * @param NpcServerMessageBuilder $npcServerMessageBuilder
     */
    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    /**
     * Conjure on movement.
     *
     * @param Character $character
     * @return void
     */
    public function movementConjure(Character $character) {

        if (CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty()) {
            return;
        }

        $x = $this->getXPosition();
        $y = $this->getYPosition();


        $invalidMaps = [
            GameMap::where('name', MapNameValue::PURGATORY)->first()->id
        ];

        $monster = Monster::where('is_celestial_entity', true)
                          ->whereNotIn('game_map_id', $invalidMaps)
                          ->where('celestial_type', '!=', CelestialType::KING_CELESTIAL)
                          ->inRandomOrder()
                          ->first();

        $healthRange          = explode('-', $monster->health_range);
        $currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;

        CelestialFight::create([
            'monster_id'      => $monster->id,
            'character_id'    => null,
            'conjured_at'     => now(),
            'x_position'      => $x,
            'y_position'      => $y,
            'damaged_kingdom' => false,
            'stole_treasury'  => false,
            'weakened_morale' => false,
            'current_health'  => $currentMonsterHealth,
            'max_health'      => $currentMonsterHealth,
            'type'            => CelestialConjureType::PUBLIC,
        ]);

        $plane = $monster->gameMap->name;

        $types = ['has awoken', 'has angered', 'has enraged', 'has set free', 'has set loose'];
        $randomIndex = rand(0, count($types) - 1);

        event(new GlobalMessageEvent($character->name . ' ' . $types[$randomIndex] . ': ' . $monster->name . ' on the ' . $plane . ' plane at (X/Y): ' . $x . '/' . $y));
    }

    /**
     * Paid to conjure the beast.
     *
     * @param Monster $monster
     * @param Character $character
     * @param string $type
     * @return void
     * @throws \Exception
     */
    public function conjure(Monster $monster, Character $character, string $type) {
        $x = $this->getXPosition();
        $y = $this->getYPosition();

        $healthRange          = explode('-', $monster->health_range);
        $currentMonsterHealth = rand($healthRange[0], $healthRange[1]);

        $celestialFight = CelestialFight::create([
            'monster_id'      => $monster->id,
            'character_id'    => $character->id,
            'conjured_at'     => now(),
            'x_position'      => $x,
            'y_position'      => $y,
            'damaged_kingdom' => false,
            'stole_treasury'  => false,
            'weakened_morale' => false,
            'current_health'  => $currentMonsterHealth,
            'max_health'      => $currentMonsterHealth,
            'type'            => $type === 'private'? CelestialConjureType::PRIVATE : CelestialConjureType::PUBLIC,
        ]);

        $plane = $character->map->gameMap->name;
        $type  = new CelestialConjureType($type === 'private'? CelestialConjureType::PRIVATE : CelestialConjureType::PUBLIC);
        $npc   = Npc::where('type', NpcTypes::SUMMONER)->first();

        if ($type->isPrivate()) {
            event(new GlobalMessageEvent($monster->name . ' has been conjured to the ' . $plane . ' plane.'));

            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('location_of_conjure', $npc, $celestialFight)));
        } else if ($type->isPublic()) {
            event(new GlobalMessageEvent( $monster->name . ' has been conjured to the ' . $plane . ' plane at (x/y): ' . $x . '/' . $y));
        }

        event(new UpdateMap($character->user, resolve(LocationService::class)->getLocationData($character->refresh())));
    }

    /**
     * Are we able to conjure?
     *
     * - Used when purchasing a conjuration.
     *
     * @param Character $character
     * @param Npc $npc
     * @param string $type
     * @return bool
     */
    public function canConjure(Character $character, Npc $npc, string $type): bool {

        if (CelestialFight::where('character_id', $character->id)->get()->isNotEmpty()) {
            event(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('already_conjured', $npc)));

            return false;
        }

        if ($type === 'public' && CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty()) {
            event(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('public_exists', $npc)));

            return false;
        }

        return true;
    }

    /**
     * Can we afford the cost?
     *
     * - Only used when purchasing.
     *
     * @param Monster $monster
     * @param Character $character
     * @return bool
     */
    public function canAfford(Monster $monster, Character $character) {
        if ($monster->gold_cost > $character->gold || $monster->gold_dust_cost > $character->gold_dust) {
            return false;
        }

        return true;
    }

    /**
     * Pay the cost.
     *
     * - Only used when purchasing.
     *
     * @param Monster $monster
     * @param Character $character
     * @return void
     */
    public function handleCost(Monster $monster, Character $character): void {
        $character->update([
            'gold'      => $character->gold - $monster->gold_cost,
            'gold_dust' => $character->gold_dust - $monster->gold_dust_cost,
        ]);

        $user           = $character->user;
        $characterMapId = $character->map->game_map_id;
        $npc            = Npc::where('type', NpcTypes::SUMMONER)->where('game_map_id', $characterMapId)->first();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('paid_conjuring', $npc), true));
    }

    /**
     * Get random X position for the beast.
     *
     * @return int
     */
    protected function getXPosition(): int {
        return CoordinatesCache::getFromCache()['x'][rand(CoordinatesCache::getFromCache()['x'][0], (count(CoordinatesCache::getFromCache()['x']) - 1))];
    }

    /**
     * Get random Y  position for the beast.
     *
     * @return int
     */
    protected function getYPosition(): int {
        return CoordinatesCache::getFromCache()['y'][rand(CoordinatesCache::getFromCache()['y'][0], (count(CoordinatesCache::getFromCache()['y']) - 1))];
    }
}
